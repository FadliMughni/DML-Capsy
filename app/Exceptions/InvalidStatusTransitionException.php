<?php

namespace App\Exceptions;

use App\Enums\InquiryStatus;
use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public static function invalidOrder(InquiryStatus $from, InquiryStatus $to): self
    {
        return new self("Status inquiry tidak dapat diubah dari \"{$from->label()}\" menjadi \"{$to->label()}\".");
    }

    public static function unauthorizedRole(InquiryStatus $to): self
    {
        return new self("Anda tidak berwenang mengubah status inquiry menjadi \"{$to->label()}\".");
    }

    public static function precondition(string $message): self
    {
        return new self($message);
    }
}
