<?php

namespace App\Core\Validation;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, $value, string $rule): void
    {
        if (str_contains($rule, ':')) {
            [$ruleName, $parameter] = explode(':', $rule);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->errors[$field][] = "$field is required.";
                }
                break;
            case 'numeric':
                if ($value !== null && !is_numeric($value)) {
                    $this->errors[$field][] = "$field must be a number.";
                }
                break;
            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->errors[$field][] = "$field must be a string.";
                }
                break;
            case 'min':
                if ($value !== null && is_string($value) && mb_strlen($value) < (int)$parameter) {
                    $this->errors[$field][] = "$field must be at least $parameter characters.";
                }
                break;
            case 'max':
                if ($value !== null && is_string($value) && mb_strlen($value) > (int)$parameter) {
                    $this->errors[$field][] = "$field must not exceed $parameter characters.";
                }
                break;
        }
    }
}
