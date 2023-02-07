<?php

namespace Mockable;

use Exception;
use ReflectionClass;
use ReflectionException;

class MockGenerator
{
    private string $classFQN;
    private string $classNamespace;
    private string $newMockedClassName;
    private string $newMockedFunctionName;
    private string $newMockedClassFileName;

    /**
     * @throws Exception
     */
    public function __construct(string $classFQN)
    {
        $this->classFQN = $classFQN;
        $classNameParts = explode('\\', $this->classFQN);
        $originalClassName = array_pop($classNameParts);
        $this->classNamespace = implode('/', $classNameParts);
        $this->newMockedClassName = $this->renameClassToMockedClassName($originalClassName);
        $this->newMockedClassFileName = $this->newMockedClassName . "_function_mocked_" . random_int(1, 1000) . '.php';
    }

    /**
     * @throws ReflectionException
     */
    public function mockFunctionReturnValue(string $functionName, string $returnValue): object
    {
        $sourceCode = $this->getSourceCode($this->getClassToMock());
        $newSourceCode = $this->replaceClassName($sourceCode);
        $newSourceCode = $this->replaceFunctionContents($newSourceCode, $functionName, $returnValue);

        $this->saveAndLoadSourceCode($newSourceCode, $this->newMockedClassName);

        $fullMockedClassName = $this->classNamespace . '\\' . $this->newMockedClassName;

        return new $fullMockedClassName;

    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function mockFunction(string $functionName, callable $replacementFunction): object
    {
        $this->newMockedFunctionName = $this->renameMockedFunction($functionName);
        $sourceCode = $this->getSourceCode($this->getClassToMock());
        $newSourceCode = $this->replaceClassName($sourceCode);
        $newSourceCode = $this->removeFunctionFromSourceCode($newSourceCode, $functionName);

        $this->saveAndLoadSourceCode($newSourceCode);
        $fullMockedClassName = $this->classNamespace . '\\' . $this->newMockedClassName;

        $newMockedClass = new $fullMockedClassName;
        $this->attachCallableToMockedClass($newMockedClass, $replacementFunction);
        return $newMockedClass;
    }

    private function renameClassToMockedClassName(string $className): string
    {
        return 'function_mocked_class_' . strtolower($className);
    }

    /**
     * @throws ReflectionException
     */
    private function getClassToMock(): ReflectionClass
    {
        return new ReflectionClass($this->classFQN);
    }

    private function getSourceCode(ReflectionClass $classToMock): string
    {
        $filePath = $classToMock->getFileName();
        return file_get_contents($filePath);
    }

    private function replaceClassName(string $sourceCodeString): string
    {
        $template = "class $this->newMockedClassName {\n";
        $classRegex = '/class(.*)\s/';

        return preg_replace($classRegex, $template, $sourceCodeString);
    }

    private function replaceFunctionContents(
        string $sourceCodeString,
        string $functionName,
        string $replacement): string
    {
        $replacement = str_replace("/'/", "\'", $replacement);
        $template = "function $functionName() { return '$replacement'; }";

        $functionRegex = "/function\s($functionName)\([\s\w$,]*\)\n\s*\{(\n[\d\w\s$->]*)}/";
        return preg_replace($functionRegex, $template, $sourceCodeString);
    }

    /**
     * @throws Exception
     */
    private function renameMockedFunction(string $functionName): string
    {
        return "mocked_function_" . random_int(1, 1000) . "_$functionName";
    }

    private function removeFunctionFromSourceCode(string $sourceCodeString, string $functionName): array|string|null
    {
        $newFunctionSignature = "public function $functionName() ";
        $invokeNewFunction =  $newFunctionSignature . '{ return call_user_func($this->' .
            $this->newMockedFunctionName .
            ');}';

        $functionRemovalRegex = "/\w*\sfunction\s$functionName\(\).*[\n\s]*\{.*[.\n\s\w';]*\}/";
        return preg_replace($functionRemovalRegex, $invokeNewFunction, $sourceCodeString);
    }

    private function attachCallableToMockedClass(
        object   $mockedClass,
        callable $replacementFunction): void
    {
        $mockedClass->{$this->newMockedFunctionName} = $replacementFunction;
    }

    private function saveAndLoadSourceCode(string $sourceCode): void
    {
        file_put_contents($this->newMockedClassFileName, $sourceCode);
        require_once $this->newMockedClassFileName;
    }

    public function destroy(): void
    {
        if (!empty($this->newMockedClassFileName)) {
            unlink($this->newMockedClassFileName);
        }
    }

    public function showMutatedFunctions($mockedClass): array
    {
        return array_diff(
            array_keys(get_object_vars($mockedClass)),
            array_keys(get_class_vars(get_class($mockedClass)))
        );
    }
}

