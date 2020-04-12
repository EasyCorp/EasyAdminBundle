<?php

namespace EasyCorp\Bundle\EasyAdminBundle\Maker;

/**
 * Building a full and complex class using BuilderFactory from PhpParser
 * was too difficult. That's why we use this basic code builder instead.
 *
 * Generated code is later parsed with PhpParser, so we ensure that code
 * is valid and we apply a basic formatting to it.
 */
final class CodeBuilder
{
    private $beforeCode = [];
    private $useStatements = [];
    private $code;

    private function __construct()
    {
        $this->beforeCode[] = '<?php ';
    }

    public static function new(): self
    {
        return new self();
    }

    public function openBrace(): self
    {
        $this->code[] = '{';

        return $this;
    }

    public function closeBrace(): self
    {
        $this->code[] = '}';

        return $this;
    }

    public function semiColon(): self
    {
        $this->code[] = ';';

        return $this;
    }

    public function equals(): self
    {
        $this->code[] = ' = ';

        return $this;
    }

    public function newLine(): self
    {
        $this->code[] = "\n";

        return $this;
    }

    public function _namespace(string $namespace)
    {
        $this->beforeCode[] = sprintf('namespace %s;', $namespace);

        return $this;
    }

    public function _use(string $useFqcn): self
    {
        $this->useStatements[] = sprintf('use %s;', $useFqcn);

        return $this;
    }

    public function _class(string $classFqcn): self
    {
        $this->code[] = sprintf('class %s', $classFqcn);

        return $this;
    }

    public function _extends(string $className): self
    {
        $this->code[] = sprintf(' extends %s', $className);

        return $this;
    }

    public function _public(): self
    {
        $this->code[] = 'public ';

        return $this;
    }

    public function _static(): self
    {
        $this->code[] = 'static ';

        return $this;
    }

    public function _function(): self
    {
        $this->code[] = 'function ';

        return $this;
    }

    public function _method(string $name, array $arguments = [], string $returnType = null): self
    {
        $this->code[] = sprintf('%s(%s)', $name, implode(', ', $arguments));
        if (null !== $returnType) {
            $this->code[] = sprintf(': %s', $returnType);
        }

        return $this;
    }

    public function _methodCall(string $name, array $arguments = []): self
    {
        $this->code[] = sprintf('->%s(%s)', $name, $this->formatArgumentsAsString($arguments));

        return $this;
    }

    public function _variableName(string $name): self
    {
        $this->code[] = sprintf('$%s', $name);

        return $this;
    }

    public function _variableValue(string $value): self
    {
        $this->code[] = sprintf(' = %s', $value);

        return $this;
    }

    public function _staticCall(string $className, string $methodOrVariable, array $arguments = []): self
    {
        $this->code[] = sprintf('%s::%s(%s)', $className, $methodOrVariable, $this->formatArgumentsAsString($arguments));

        return $this;
    }

    public function _return(): self
    {
        $this->code[] = 'return ';

        return $this;
    }

    public function getAsString(): string
    {
        $useStatements = array_unique($this->useStatements);
        sort($useStatements);

        return implode('', $this->beforeCode)
            .' '
            .implode('', $useStatements)
            .' '
            .implode('', $this->code);
    }

    private function formatArgumentsAsString(array $arguments): string
    {
        $formattedArguments = [];

        foreach ($arguments as $key => $argument) {
            if (\is_string($argument)) {
                $formattedArgument = sprintf("'%s'", str_replace("'", "\'", $argument));
            } elseif (\is_bool($argument)) {
                $formattedArgument = strtolower(var_export($argument, true));
            } elseif (\is_array($argument)) {
                $formattedArrayElements = array_map(function ($value) {
                    return \is_string($value) ? "'".str_replace("'", "\'", $value)."'" : $value;
                }, $argument);

                $formattedArgument = '['.implode(', ', $formattedArrayElements).']';
            } else {
                $formattedArgument = str_replace("\n", '', var_export($argument, true));
            }

            $formattedArguments[] = \is_int($key) ? $formattedArgument : sprintf("'%s' => %s", $key, $formattedArgument);
        }

        return implode(', ', $formattedArguments);
    }
}
