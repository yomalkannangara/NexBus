<?php
namespace App\Support;

class Validator
{
    private array $data;

    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }


    private function val(string $field): ?string
    {
        $v = $this->data[$field] ?? null;
        return ($v !== null) ? trim((string) $v) : null;
    }

    private function addError(string $field, string $message): static
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function required(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v === null || $v === '') {
            $this->addError($field, "{$label} is required and cannot be empty.");
        }
        return $this;
    }

    public function integer(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "{$label} must be a whole number.");
        }
        return $this;
    }

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

    public function numeric(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !is_numeric($v)) {
            $this->addError($field, "{$label} must be a numeric value.");
        }
        return $this;
    }

    public function inList(string $field, string $label, array $allowed): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && !in_array($v, $allowed, true)) {
            $list = implode(', ', $allowed);
            $this->addError($field, "{$label} must be one of: {$list}.");
        }
        return $this;
    }

    public function minLength(string $field, string $label, int $min): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && mb_strlen($v) < $min) {
            $this->addError($field, "{$label} must be at least {$min} characters long.");
        }
        return $this;
    }

    public function maxLength(string $field, string $label, int $max): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '' && mb_strlen($v) > $max) {
            $this->addError($field, "{$label} must not exceed {$max} characters.");
        }
        return $this;
    }

    public function length(string $field, string $label, int $min, int $max): static
    {
        return $this->minLength($field, $label, $min)->maxLength($field, $label, $max);
    }

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

    public function sriLankanPhone(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
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

    public function manufacturedYear(string $field, string $label): static
    {
        $v = $this->val($field);
        if ($v !== null && $v !== '') {
            $y = (int) $v;
            $now = (int) date('Y');
            if (!preg_match('/^\d{4}$/', $v) || $y < 1950 || $y > $now) {
                $this->addError(
                    $field,
                    "{$label} must be a valid 4-digit year between 1950 and {$now}."
                );
            }
        }
        return $this;
    }

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

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    public function firstError(): ?string
    {
        return !empty($this->errors) ? array_values($this->errors)[0] : null;
    }
}



$phone = trim((string) ($_POST['emergency_contacts'] ?? ''));
if ($phone !== '' && !preg_match('/^07\d{8}$/', $phone)) {
    echo "<script>
                                alert('Phone number must be 10 digits and start with 07.');
                                window.history.back(); 
                         </script>";
    exit;
}

$phone = trim((string) ($_POST['phone'] ?? ''));
if ($phone !== '' && !preg_match('/^07\d{8}$/', $phone)) {
    $this->flashErrors(['phone' => 'Phone number must be 10 digits and start with 07.']);
    $this->flashOldInput($_POST);
    return $this->redirect('/B/drivers?msg=validation_error');
}




$registrationDate = trim((string) ($_POST['registration_date'] ?? ''));
if ($registrationDate !== '') {
    $date = \DateTime::createFromFormat('Y-m-d', $registrationDate);
    $isValidDate = $date && $date->format('Y-m-d') === $registrationDate;
    $today = date('Y-m-d');

    if (!$isValidDate || $registrationDate > $today) {
        $this->flashErrors([
            'registration_date' => 'Registration date cannot be a future date.',
        ]);
        $this->flashOldInput($_POST);
        return $this->redirect('/B/drivers?msg=validation_error');
    }
}


$chassisNo = trim((string) ($_POST['chassis_no'] ?? ''));
if (!preg_match('/^[A-Za-z][0-9]+[A-Za-z]$/', $chassisNo)) {
    $this->flashErrors([
        'chassis_no' => 'Chassis number must start and end with a letter, with digits in between.',
    ]);
    $this->flashOldInput($_POST);
    return $this->redirect('/B/fleet?msg=validation_error');
}



$nic = strtoupper(preg_replace('/\s+/', '', trim((string) ($_POST['nic'] ?? ''))));
if ($nic === '' || !preg_match('/^(?:\d{9}[VX]|\d{12})$/', $nic)) {
    $this->flashErrors([
        'nic' => 'NIC must be valid (old: 9 digits + V/X, or new: 12 digits).',
    ]);
    $this->flashOldInput($_POST);
    return $this->redirect('/B/drivers?msg=validation_error');
}
$_POST['nic'] = $nic;