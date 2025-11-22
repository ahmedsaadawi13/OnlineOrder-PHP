<?php

namespace App\Validators;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validate data against rules
     */
    public function validate(array $rules): bool
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);

            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Apply a single validation rule
     */
    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;

        // Parse rule with parameters (e.g., "min:5")
        $params = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramString] = explode(':', $rule, 2);
            $params = explode(',', $paramString);
        }

        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "$field is required");
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "$field must be a valid email");
                }
                break;

            case 'min':
                $min = (int) ($params[0] ?? 0);
                if ($value && strlen($value) < $min) {
                    $this->addError($field, "$field must be at least $min characters");
                }
                break;

            case 'max':
                $max = (int) ($params[0] ?? 0);
                if ($value && strlen($value) > $max) {
                    $this->addError($field, "$field must not exceed $max characters");
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, "$field must be numeric");
                }
                break;

            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, "$field must be an integer");
                }
                break;

            case 'string':
                if ($value && !is_string($value)) {
                    $this->addError($field, "$field must be a string");
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "$field must be a valid URL");
                }
                break;

            case 'phone':
                if ($value && !preg_match('/^[\d\s\+\-\(\)]+$/', $value)) {
                    $this->addError($field, "$field must be a valid phone number");
                }
                break;

            case 'alpha':
                if ($value && !ctype_alpha(str_replace(' ', '', $value))) {
                    $this->addError($field, "$field must contain only letters");
                }
                break;

            case 'alphanumeric':
                if ($value && !ctype_alnum(str_replace(' ', '', $value))) {
                    $this->addError($field, "$field must contain only letters and numbers");
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($this->data[$confirmField] ?? null)) {
                    $this->addError($field, "$field confirmation does not match");
                }
                break;

            case 'in':
                if ($value && !in_array($value, $params)) {
                    $this->addError($field, "$field must be one of: " . implode(', ', $params));
                }
                break;

            case 'array':
                if ($value && !is_array($value)) {
                    $this->addError($field, "$field must be an array");
                }
                break;

            case 'boolean':
                if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', true, false], true)) {
                    $this->addError($field, "$field must be a boolean");
                }
                break;

            case 'date':
                if ($value && !strtotime($value)) {
                    $this->addError($field, "$field must be a valid date");
                }
                break;

            case 'unique':
                // Format: unique:table,column
                if (count($params) >= 2) {
                    [$table, $column] = $params;
                    $exists = \App\Core\Database::queryOne(
                        "SELECT COUNT(*) as count FROM `$table` WHERE `$column` = ?",
                        [$value]
                    );
                    if ($exists && $exists['count'] > 0) {
                        $this->addError($field, "$field already exists");
                    }
                }
                break;

            case 'exists':
                // Format: exists:table,column
                if (count($params) >= 2) {
                    [$table, $column] = $params;
                    $exists = \App\Core\Database::queryOne(
                        "SELECT COUNT(*) as count FROM `$table` WHERE `$column` = ?",
                        [$value]
                    );
                    if (!$exists || $exists['count'] == 0) {
                        $this->addError($field, "$field does not exist");
                    }
                }
                break;
        }
    }

    /**
     * Add an error message
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get first error message
     */
    public function firstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }

    /**
     * Static helper for quick validation
     */
    public static function make(array $data, array $rules): self
    {
        $validator = new self($data);
        $validator->validate($rules);
        return $validator;
    }
}
