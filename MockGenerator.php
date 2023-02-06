<?php

namespace FunctionMock;

use ReflectionClass;

class MockGenerator
{
    private string $classFQN;
    private string $classNamespace;
    private string $originalClassName;
    private string $newMockedClassName;
    private string $newMockedFunctionName;
    private string $newMockedClassFileName = '';

    public function __construct(string $classFQN)
    {
        $this->classFQN = $classFQN;
        $classNameParts = explode('\\', $this->classFQN);
        $this->originalClassName = array_pop($classNameParts);
        $this->classNamespace = implode('/', $classNameParts);
        $this->newMockedClassName = $this->renameClassToMockedClassName($this->originalClassName);
        $this->newMockedClassFileName = $this->newMockedClassName . "_function_mocked_" . rand(1, 1000) . '.php';
    }

    public function mockFunctionReturnValue(string $functionName, string $returnValue): object
    {
        $sourceCode = $this->getSourceCode($this->getClassToMock());
        $newSourceCode = $this->replaceClassName($sourceCode);
        $newSourceCode = $this->replaceFunctionContents($newSourceCode, $functionName, $returnValue);

        $this->saveAndLoadSourceCode($newSourceCode, $this->newMockedClassName);

        $fullMockedClassName = $this->classNamespace . '\\' . $this->newMockedClassName;

        return new $fullMockedClassName;

    }

    public function mockFunction(string $functionName, callable $replacementFunction): object
    {
        $this->newMockedFunctionName = $this->renameMockedFunction($functionName);
        $sourceCode = $this->getSourceCode($this->getClassToMock());
        $newSourceCode = $this->replaceClassName($sourceCode);
        $newSourceCode = $this->removeFunctionFromSourceCode($newSourceCode, $functionName);

        $this->saveAndLoadSourceCode($newSourceCode);
        $fullMockedClassName = $this->classNamespace . '\\' . $this->newMockedClassName;

        $newMockedClass = new $fullMockedClassName;
        $this->attachCallableToMockedClass($newMockedClass, $functionName, $replacementFunction);
        return $newMockedClass;
    }

    private function renameClassToMockedClassName(string $className)
    {
        $mockedClassName = 'function_mocked_class_' . strtolower($className);

        return $mockedClassName;
    }

    private function getClassToMock(): ReflectionClass
    {
        return new \ReflectionClass($this->classFQN);
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

        $rewrittenSourceCode = preg_replace($classRegex, $template, $sourceCodeString);

        return $rewrittenSourceCode;
    }

    private function replaceFunctionContents(string $sourceCodeString, string $functionName, string $replacement): string
    {
        $replacement = preg_replace("/'/", "\'", $replacement);
        $template = "function $functionName() { return '$replacement'; }";

        $functionRegex = "/function\s($functionName)\([\s\w$,]*\)\n\s*\{(\n[\d\w\s$->]*)}/";
        $rewrittenSourceCode = preg_replace($functionRegex, $template, $sourceCodeString);
        return $rewrittenSourceCode;
    }

    private function renameMockedFunction(string $functionName)
    {
        return "mocked_function_" . rand(1, 1000) . "_$functionName";
    }

    private function removeFunctionFromSourceCode(string $sourceCodeString, string $functionName)
    {
        $newFunctionSignature = "public function $functionName() ";
        $invokeNewFunction =  $newFunctionSignature . '{ call_user_func($this->' .
            $this->newMockedFunctionName .
            ');}';

        $functionRemovalRegex = "/\w*\sfunction\s$functionName\(\).*[\n\s]*\{.*[.\n\s\w';]*\}/";
        $rewrittenSourceCode = preg_replace($functionRemovalRegex, $invokeNewFunction, $sourceCodeString);
        return $rewrittenSourceCode;
    }

    private function attachCallableToMockedClass(object &$mockedClass, string $functionName, callable $replacementFunction)
    {
        $mockedClass->{$this->newMockedFunctionName} = $replacementFunction;
    }

    private function saveAndLoadSourceCode(string $sourceCode)
    {
        file_put_contents($this->newMockedClassFileName, $sourceCode);
        require_once $this->newMockedClassFileName;
    }

    public function destroy()
    {
        if (!empty($this->newMockedClassFileName)) {
            unlink($this->newMockedClassFileName);
        }
    }
}

