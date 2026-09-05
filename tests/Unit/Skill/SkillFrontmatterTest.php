<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill;

use EasyCorp\Bundle\EasyAdminBundle\Tests\Unit\Skill\Support\SkillFile;
use PHPUnit\Framework\TestCase;

class SkillFrontmatterTest extends TestCase
{
    private const ALLOWED_KEYS = ['name', 'description', 'license', 'metadata'];
    private const EXPECTED_NAME = 'easyadmin';
    private const NAME_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';
    private const SAFE_DESCRIPTION_STYLES = [SkillFile::STYLE_FOLDED, SkillFile::STYLE_DOUBLE_QUOTED];
    private const MAX_DESCRIPTION_LENGTH = 1024;
    private const REQUIRED_DESCRIPTION_PHRASE = 'Do not use';

    protected function setUp(): void
    {
        if (!SkillFile::exists()) {
            self::fail(SkillFile::missingFileMessage());
        }
    }

    public function testFileStartsWithTheFrontmatterDelimiter(): void
    {
        $contents = SkillFile::skill()->contents();

        self::assertStringStartsWith("---\n", $contents, sprintf('%s must start with a "---" line that opens the YAML front matter, otherwise agent tools skip the skill.', SkillFile::RELATIVE_PATH));
    }

    public function testFrontmatterOnlyUsesTheKeysOfTheAgentSkillsStandard(): void
    {
        $unexpectedKeys = array_values(array_diff(array_keys(SkillFile::skill()->frontmatter()), self::ALLOWED_KEYS));

        self::assertSame([], $unexpectedKeys, sprintf('The front matter of %s must only define these keys: %s. Move anything else under "metadata".', SkillFile::RELATIVE_PATH, implode(', ', self::ALLOWED_KEYS)));
    }

    public function testNameMatchesTheDirectoryThatContainsTheSkill(): void
    {
        $name = SkillFile::skill()->frontmatter()['name'] ?? null;

        self::assertSame(self::EXPECTED_NAME, $name, sprintf('The "name" of %s must be "%s", because agents refer to the skill by that name.', SkillFile::RELATIVE_PATH, self::EXPECTED_NAME));
        self::assertSame(basename(SkillFile::skillDir()), $name, sprintf('The "name" of %s must match the "%s" directory that contains it.', SkillFile::RELATIVE_PATH, basename(SkillFile::skillDir())));
    }

    public function testNameUsesLowercaseHyphenatedCharactersOnly(): void
    {
        $name = SkillFile::skill()->frontmatter()['name'] ?? '';

        self::assertMatchesRegularExpression(self::NAME_PATTERN, $name, sprintf('The "name" of %s must be lowercase letters, digits and single hyphens (pattern %s), because it is used as a directory name.', SkillFile::RELATIVE_PATH, self::NAME_PATTERN));
    }

    public function testDescriptionIsNeverWrittenAsAPlainScalar(): void
    {
        $style = SkillFile::skill()->descriptionStyle();

        self::assertContains($style, self::SAFE_DESCRIPTION_STYLES, sprintf('The "description" of %s is written as a %s scalar, but it must be a folded block scalar (">-") or a double-quoted string. A plain scalar that contains ": " or " #" breaks real YAML parsers, such as the one in "npx skills add": ": " starts a mapping key and " #" starts a comment that truncates the value, so the skill is discarded as having no description.', SkillFile::RELATIVE_PATH, $style));
    }

    public function testDescriptionIsNotEmptyAndFitsTheLengthLimit(): void
    {
        $description = SkillFile::skill()->frontmatter()['description'] ?? '';

        self::assertNotSame('', trim($description), sprintf('The "description" of %s is empty. Agents decide whether to load a skill from its description alone.', SkillFile::RELATIVE_PATH));
        self::assertLessThanOrEqual(self::MAX_DESCRIPTION_LENGTH, \strlen($description), sprintf('The "description" of %s is %d characters long and the limit is %d.', SkillFile::RELATIVE_PATH, \strlen($description), self::MAX_DESCRIPTION_LENGTH));
    }

    public function testDescriptionStartsWithAThirdPersonVerb(): void
    {
        $description = SkillFile::skill()->frontmatter()['description'] ?? '';
        $firstWord = preg_split('/\s+/', trim($description))[0] ?? '';

        self::assertMatchesRegularExpression('/^[A-Z][a-z]+s$/', $firstWord, sprintf('The "description" of %s starts with "%s", but it must start with a capitalized third-person verb such as "Creates" or "Configures". That is the wording agents are trained to match.', SkillFile::RELATIVE_PATH, $firstWord));
    }

    public function testDescriptionSaysWhenTheSkillMustNotBeUsed(): void
    {
        $description = SkillFile::skill()->frontmatter()['description'] ?? '';

        self::assertStringContainsString(self::REQUIRED_DESCRIPTION_PHRASE, $description, sprintf('The "description" of %s must contain a "%s" clause, so agents stop loading the skill for plain Symfony controllers, other admin bundles and EasyAdmin 1-4 codebases.', SkillFile::RELATIVE_PATH, self::REQUIRED_DESCRIPTION_PHRASE));
    }
}
