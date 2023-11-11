<?php
namespace Pzzd\LoginDemo;

class Validator {


    public function errors(): array 
    {
        return [];
    }

    public function checkWithinRange($value, $from, $to, $message): bool 
    {
        return true;
    }

    public function checkMaxLength($value, $length, $message): bool
    {
        return true;
    }

    public function checkEmail($value): bool
    {
        return true;
    }
}