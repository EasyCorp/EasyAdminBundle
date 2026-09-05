<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Ai;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Renders, installs and inspects the copies of the EasyAdmin skill for AI coding agents.
 *
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final readonly class SkillInstaller
{
    public const SKILL_NAME = 'easyadmin';
    private const SKILL_FILE_NAME = 'SKILL.md';
    private const INSTALLED_BY = 'easyadmin:ai:install';

    public function __construct(
        private string $projectDir,
        private string $skillsSourceDir,
        private string $guidelinesTemplatePath,
        private string $version,
    ) {
    }

    public function version(): string
    {
        return $this->version;
    }

    /**
     * @return list<AgentTarget>
     */
    public function defaultTargets(): array
    {
        return AgentTarget::defaults($this->projectDir);
    }

    public function sourceSkillDir(): string
    {
        return $this->skillsSourceDir.'/'.self::SKILL_NAME;
    }

    public function sourceExists(): bool
    {
        return is_file($this->sourceSkillDir().'/'.self::SKILL_FILE_NAME);
    }

    public function skillDir(AgentTarget $target): string
    {
        return $this->projectDir.'/'.$this->relativeSkillDir($target);
    }

    public function relativeSkillDir(AgentTarget $target): string
    {
        return $target->skillsDir().'/'.self::SKILL_NAME;
    }

    public function guidelinesFilePath(AgentTarget $target): string
    {
        return $this->projectDir.'/'.$target->instructionsFile();
    }

    /**
     * Returns the contents of every skill file, indexed by their path relative
     * to the skill directory and always using "/" as the directory separator.
     *
     * @return array<string, string>
     */
    public function renderSkillFiles(): array
    {
        if (!$this->sourceExists()) {
            throw new \RuntimeException(sprintf('The AI agent skill files were not found at "%s".', $this->sourceSkillDir()));
        }

        $skillFiles = [];
        foreach ($this->findFilesIn($this->sourceSkillDir()) as $relativePath => $absolutePath) {
            $skillFiles[$relativePath] = $this->readFile($absolutePath);
        }

        $skillFiles[self::SKILL_FILE_NAME] = $this->addInstallationMetadata($skillFiles[self::SKILL_FILE_NAME]);
        ksort($skillFiles);

        return $skillFiles;
    }

    public function status(AgentTarget $target): SkillStatus
    {
        $skillDir = $this->skillDir($target);
        if (!is_dir($skillDir)) {
            return SkillStatus::NotInstalled;
        }

        $installedFiles = $this->findFilesIn($skillDir);
        if ([] === $installedFiles) {
            return SkillStatus::NotInstalled;
        }

        if (!$this->isManagedByEasyAdmin($target)) {
            return SkillStatus::NotManagedByEasyAdmin;
        }

        $installedContents = [];
        foreach ($installedFiles as $relativePath => $absolutePath) {
            $installedContents[$relativePath] = $this->readFile($absolutePath);
        }
        ksort($installedContents);

        return $this->renderSkillFiles() === $installedContents ? SkillStatus::UpToDate : SkillStatus::Outdated;
    }

    /**
     * @return list<AgentTarget>
     */
    public function installedTargets(): array
    {
        $installedTargets = [];
        foreach (AgentTarget::cases() as $target) {
            if ($this->isManagedByEasyAdmin($target)) {
                $installedTargets[] = $target;
            }
        }

        return $installedTargets;
    }

    public function installedVersion(AgentTarget $target): ?string
    {
        $skillFilePath = $this->skillDir($target).'/'.self::SKILL_FILE_NAME;
        if (!is_file($skillFilePath)) {
            return null;
        }

        $frontmatter = $this->readFrontmatter($this->readFile($skillFilePath));
        if (null === $frontmatter || 1 !== preg_match('/^[ \t]*easyadmin-version:[ \t]*[\'"]?([^\'"\r\n]+?)[\'"]?[ \t]*$/m', $frontmatter, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Returns the path of every written file, relative to the project directory.
     *
     * @return list<string>
     */
    public function install(AgentTarget $target): array
    {
        $skillFiles = $this->renderSkillFiles();
        $skillDir = $this->skillDir($target);
        $filesystem = new Filesystem();

        $writtenFiles = [];
        foreach ($skillFiles as $relativePath => $contents) {
            $filesystem->dumpFile($skillDir.'/'.$relativePath, $contents);
            $writtenFiles[] = $this->relativeSkillDir($target).'/'.$relativePath;
        }

        $this->removeObsoleteFiles($skillDir, array_keys($skillFiles));

        return $writtenFiles;
    }

    public function renderGuidelines(string $instructionsFile): string
    {
        $template = $this->readFile($this->guidelinesTemplatePath);
        $guidelines = strtr($template, [
            '%EASYADMIN_VERSION%' => $this->version,
            '%SKILL_PATH%' => $this->skillPathFor($instructionsFile),
        ]);

        // the block is normalized exactly like GuidelinesFileWriter does when writing it,
        // so that "easyadmin:ai:update --check" never reports a permanent difference
        return GuidelinesFileWriter::normalizeBlock($guidelines);
    }

    private function skillPathFor(string $instructionsFile): string
    {
        $matchingTargets = array_values(array_filter(
            AgentTarget::cases(),
            static fn (AgentTarget $target): bool => $target->instructionsFile() === $instructionsFile
        ));

        if ([] === $matchingTargets) {
            throw new \InvalidArgumentException(sprintf('The "%s" file is not the AI instructions file of any supported AI coding agent.', $instructionsFile));
        }

        foreach ($matchingTargets as $target) {
            if ($this->isManagedByEasyAdmin($target)) {
                return $this->relativeSkillDir($target).'/'.self::SKILL_FILE_NAME;
            }
        }

        return $this->relativeSkillDir($matchingTargets[0]).'/'.self::SKILL_FILE_NAME;
    }

    private function isManagedByEasyAdmin(AgentTarget $target): bool
    {
        $skillFilePath = $this->skillDir($target).'/'.self::SKILL_FILE_NAME;
        if (!is_file($skillFilePath)) {
            return false;
        }

        $frontmatter = $this->readFrontmatter($this->readFile($skillFilePath));
        if (null === $frontmatter) {
            return false;
        }

        return 1 === preg_match('/^[ \t]*installed-by:[ \t]*[\'"]?'.preg_quote(self::INSTALLED_BY, '/').'[\'"]?[ \t]*$/m', $frontmatter);
    }

    private function addInstallationMetadata(string $skillFileContents): string
    {
        $frontmatter = $this->readFrontmatter($skillFileContents);
        if (null === $frontmatter) {
            throw new \RuntimeException(sprintf('The "%s" file must start with a YAML frontmatter block delimited by "---" lines.', $this->sourceSkillDir().'/'.self::SKILL_FILE_NAME));
        }

        $eol = str_contains($skillFileContents, "\r\n") ? "\r\n" : "\n";
        $lines = explode("\n", rtrim(str_replace("\r\n", "\n", $frontmatter), "\n"));
        $installationMetadata = [
            sprintf('  easyadmin-version: \'%s\'', $this->version),
            sprintf('  installed-by: \'%s\'', self::INSTALLED_BY),
        ];

        $metadataIndex = null;
        foreach ($lines as $index => $line) {
            if (1 === preg_match('/^metadata:[ \t]*$/', $line)) {
                $metadataIndex = $index;
                break;
            }

            if (1 === preg_match('/^metadata:[ \t]*\S/', $line)) {
                throw new \RuntimeException(sprintf('The "metadata" key of the "%s" file must use the YAML block syntax (a "metadata:" line followed by indented keys) instead of the inline syntax.', $this->sourceSkillDir().'/'.self::SKILL_FILE_NAME));
            }
        }

        if (null === $metadataIndex) {
            $lines[] = 'metadata:';
            $lines = array_merge($lines, $installationMetadata);
        } else {
            $insertIndex = $metadataIndex + 1;
            for ($index = $metadataIndex + 1, $totalLines = \count($lines); $index < $totalLines; ++$index) {
                if ('' === trim($lines[$index])) {
                    continue;
                }

                if (1 !== preg_match('/^[ \t]/', $lines[$index])) {
                    break;
                }

                $insertIndex = $index + 1;
            }

            array_splice($lines, $insertIndex, 0, $installationMetadata);
        }

        $newFrontmatter = implode($eol, $lines).$eol;
        $frontmatterStart = \strlen($this->frontmatterDelimiter($skillFileContents));

        return substr($skillFileContents, 0, $frontmatterStart).$newFrontmatter.substr($skillFileContents, $frontmatterStart + \strlen($frontmatter));
    }

    /**
     * Returns the contents between the two "---" delimiters of the YAML frontmatter block.
     */
    private function readFrontmatter(string $contents): ?string
    {
        $delimiter = $this->frontmatterDelimiter($contents);
        if ('' === $delimiter) {
            return null;
        }

        if (1 !== preg_match('/^---[ \t]*\r?$/m', $contents, $matches, \PREG_OFFSET_CAPTURE, \strlen($delimiter))) {
            return null;
        }

        return substr($contents, \strlen($delimiter), $matches[0][1] - \strlen($delimiter));
    }

    private function frontmatterDelimiter(string $contents): string
    {
        if (1 !== preg_match('/^---[ \t]*\r?\n/', $contents, $matches)) {
            return '';
        }

        return $matches[0];
    }

    /**
     * @param list<string> $expectedFiles
     */
    private function removeObsoleteFiles(string $skillDir, array $expectedFiles): void
    {
        $filesystem = new Filesystem();
        foreach ($this->findFilesIn($skillDir) as $relativePath => $absolutePath) {
            if (!\in_array($relativePath, $expectedFiles, true)) {
                $filesystem->remove($absolutePath);
            }
        }

        // the directories are collected before removing any of them because
        // removing entries while iterating over them can skip some of the entries
        $directoryPaths = [];
        $directories = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($skillDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($directories as $directory) {
            if ($directory->isDir() && !$directory->isLink()) {
                $directoryPaths[] = $directory->getPathname();
            }
        }

        foreach ($directoryPaths as $directoryPath) {
            if (is_dir($directoryPath) && !(new \FilesystemIterator($directoryPath))->valid()) {
                $filesystem->remove($directoryPath);
            }
        }
    }

    /**
     * @return array<string, string> the path of each file relative to $directory => its absolute path
     */
    private function findFilesIn(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $absolutePath = $file->getPathname();
            $files[str_replace('\\', '/', substr($absolutePath, 1 + \strlen($directory)))] = $absolutePath;
        }

        ksort($files);

        return $files;
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new \RuntimeException(sprintf('The "%s" file could not be read.', $path));
        }

        return $contents;
    }
}
