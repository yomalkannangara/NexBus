<?php
namespace App\Support;

/**
 * Validator — centralized input validation for the Private Bus Owner role.
 *
 * Usage:
 *   $v = new Validator($_POST);
 *   $v->required('full_name', 'Full Name')
 *     ->name('full_name',  'Full Name')
 *     ->length('full_name', 'Full Name', 2, 80);
 *   if ($v->fails()) {
 *       // $v->errors() returns ['field' => 'message', ...]
 *   }
 */
class Validator
{
    /** @var array<string, mixed> Input data being validated */
    private array $data;

    /** @var array<string, string> Collected error messages [field => message] */
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /* ================================================================
     * Core helpers
     * ============================================================== */

    /** Raw value, trimmed; null if key missing */
    private function val(string $field): ?string
    {
        $v = $this->data[$field] ?? null;
        return ($v !== null) ? trim((string)$v) : null;
    }

    /** Add an error only if no error for that field yet (first-error-wins) */
    private function addError(string $field, string $message): static
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    /* ================================================================
     * 1. REQUIRED FIELDS
     * ============================================================== */

    /** Fails if field is missing, empty, or only whitespace */
    public function required(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v === null || $v === '') {
            $this->addError($field, "{$label} is required and cannot be empty.");
        }
        return $this;
    }

    /* ================================================================
     * 2. DATA TYPE VALIDATION
     * ============================================================== */

    /** Must be a plain integer */
    public function integer(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "{$label} must be a whole number.");
        }
        return $this;
    }

    /** Must be a positive integer */
    public function positiveInt(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            $n = filter_var($v, FILTER_VALIDATE_INT);
            if ($n === false || $n <= 0) {
                $this->addError($field, "{$label} must be a positive whole number.");
            }
        }
        return $this;
    }

    /** Must be numeric (int or float) */
    public function numeric(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !is_numeric($v)) {
            $this->addError($field, "{$label} must be a numeric value.");
        }
        return $this;
    }

    /** Must be one of the given allowed values (strict) */
    public function inList(string $field, string $label, array $allowed): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !in_array($v, $allowed, true)) {
            $list = implode(', ', $allowed);
            $this->addError($field, "{$label} must be one of: {$list}.");
        }
        return $this;
    }

    /* ================================================================
     * 3. LENGTH VALIDATION
     * ============================================================== */

    /** Minimum character length (only checked when field has a value) */
    public function minLength(string $field, string $label, int $min): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && mb_strlen($v) < $min) {
            $this->addError($field, "{$label} must be at least {$min} characters long.");
        }
        return $this;
    }

    /** Maximum character length */
    public function maxLength(string $field, string $label, int $max): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && mb_strlen($v) > $max) {
            $this->addError($field, "{$label} must not exceed {$max} characters.");
        }
        return $this;
    }

    /** Shorthand for min + max in one call */
    public function length(string $field, string $label, int $min, int $max): static
    {
        return $this->minLength($field, $label, $min)->maxLength($field, $label, $max);
    }

    /* ================================================================
     * 4. FORMAT VALIDATION (regex / PHP filters)
     * ============================================================== */

    /**
     * Full Name — letters (including Unicode), spaces, hyphens, apostrophes only.
     * Each word should start with a capital letter (enforced server-side in model).
     */
    public function name(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            if (!preg_match("/^[\p{L}\s'\-\.]+$/u", $v)) {
                $this->addError(
                    $field,
                    "{$label} may only contain letters, spaces, hyphens, or apostrophes."
                );
            }
        }
        return $this;
    }

    /**
     * Sri Lankan phone number.
     * Accepts: 07XXXXXXXX, +94XXXXXXXXX, 94XXXXXXXXX (10 or 12 digits with optional +/spaces).
     */
    public function sriLankanPhone(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            // Strip spaces and dashes for checking
            $clean = preg_replace('/[\s\-]/', '', $v);
            $pattern = '/^(?:\+94|94|0)[1-9]\d{8}$/';
            if (!preg_match($pattern, $clean)) {
                $this->addError(
                    $field,
                    "{$label} must be a valid Sri Lankan phone number (e.g. 0771234567 or +94771234567)."
                );
            }
        }
        return $this;
    }

    /**
     * General phone — 7–15 digits, optional leading +, spaces, hyphens.
     */
    public function phone(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            $clean = preg_replace('/[\s\-]/', '', $v);
            if (!preg_match('/^\+?\d{7,15}$/', $clean)) {
                $this->addError(
                    $field,
                    "{$label} must be a valid phone number (7–15 digits)."
                );
            }
        }
        return $this;
    }

    /**
     * Sri Lanka private bus registration — e.g. PB-1001, NA-1234
     * Pattern: 2–3 uppercase letters, hyphen, 1–6 digits.
     */
    public function busRegistration(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            if (!preg_match('/^[A-Z]{2,3}-\d{1,6}$/', strtoupper($v))) {
                $this->addError(
                    $field,
                    "{$label} must follow the format XX-0000 or XXX-0000 (e.g. PB-1001)."
                );
            }
        }
        return $this;
    }

    /**
     * Driver license number — alphanumeric, may contain hyphens or slashes.
     * e.g. B1234567, L-PRV-1001
     */
    public function licenseNo(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            if (!preg_match('/^[A-Za-z0-9\-\/]{3,30}$/', $v)) {
                $this->addError(
                    $field,
                    "{$label} must be alphanumeric and 3–30 characters (hyphens/slashes allowed)."
                );
            }
        }
        return $this;
    }

    /**
     * Chassis number — alphanumeric + hyphens, caps enforced by model.
     */
    public function chassisNo(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            if (!preg_match('/^[A-Za-z0-9\-]{3,30}$/', $v)) {
                $this->addError(
                    $field,
                    "{$label} must be 3–30 alphanumeric characters (hyphens allowed)."
                );
            }
        }
        return $this;
    }

    /**
     * Manufactured year — 4-digit year between 1950 and current year.
     */
    public function manufacturedYear(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            $y = (int)$v;
            $now = (int)date('Y');
            if (!preg_match('/^\d{4}$/', $v) || $y < 1950 || $y > $now) {
                $this->addError(
                    $field,
                    "{$label} must be a valid 4-digit year between 1950 and {$now}."
                );
            }
        }
        return $this;
    }

    /**
     * Date string in YYYY-MM-DD format.
     */
    public function date(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $v);
            if (!$dt || $dt->format('Y-m-d') !== $v) {
                $this->addError($field, "{$label} must be a valid date (YYYY-MM-DD).");
            }
        }
        return $this;
    }

    /* ================================================================
     * 5. RESULT ACCESSORS
     * ============================================================== */

    /** Returns true if any validation rule failed */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /** Returns true if all rules passed */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /** Returns all error messages keyed by field name */
    public function errors(): array
    {
        return $this->errors;
    }

    /** Returns the first error message for a specific field, or null */
    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /** Returns ONLY the first global error message (convenience) */
    public function firstError(): ?string
    {
        return !empty($this->errors) ? array_values($this->errors)[0] : null;
    }
}
