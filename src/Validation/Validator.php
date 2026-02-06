<?php

namespace App\Validation;

class Validator
{
    private array $errors = [];
    private array $data = [];

    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if validation passes
     */
    public function validate(array $data, array $rules): bool
    {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            
            foreach ($rules as $rule) {
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
        // Parse rule and parameter
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $param = $parts[1] ?? null;

        $value = $this->data[$field] ?? null;

        switch ($ruleName) {
            case 'required':
                if (!isset($this->data[$field]) || $this->isEmpty($value)) {
                    $this->addError($field, "$field is required");
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "$field must be a valid email address");
                }
                break;

            case 'min':
                if ($value && strlen($value) < (int)$param) {
                    $this->addError($field, "$field must be at least $param characters");
                }
                break;

            case 'max':
                if ($value && strlen($value) > (int)$param) {
                    $this->addError($field, "$field must not exceed $param characters");
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

            case 'positive':
                if ($value && (float)$value <= 0) {
                    $this->addError($field, "$field must be positive");
                }
                break;

            case 'in':
                $allowed = explode(',', $param);
                if ($value && !in_array($value, $allowed)) {
                    $this->addError($field, "$field must be one of: " . implode(', ', $allowed));
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "$field must be a valid URL");
                }
                break;

            case 'date':
                if ($value && !strtotime($value)) {
                    $this->addError($field, "$field must be a valid date");
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

            case 'regex':
                if ($value && !preg_match($param, $value)) {
                    $this->addError($field, "$field format is invalid");
                }
                break;

            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($this->data[$confirmField] ?? null)) {
                    $this->addError($field, "$field confirmation does not match");
                }
                break;

            case 'unique':
                // Format: unique:table,column
                [$table, $column] = explode(',', $param);
                if ($value && $this->existsInDatabase($table, $column, $value)) {
                    $this->addError($field, "$field already exists");
                }
                break;
        }
    }

    /**
     * Check if value is empty
     */
    private function isEmpty($value): bool
    {
        if ($value === null) return true;
        if (is_string($value) && trim($value) === '') return true;
        if (is_array($value) && empty($value)) return true;
        return false;
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
     * Check if value exists in database
     */
    private function existsInDatabase(string $table, string $column, $value): bool
    {
        try {
            $db = \App\Config\Database::getConnection();
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = :value";
            $stmt = $db->prepare($sql);
            $stmt->execute([':value' => $value]);
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        
        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }

    /**
     * Get errors as flat array of messages
     */
    public function getErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }
}
