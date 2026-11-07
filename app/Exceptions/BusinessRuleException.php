<?php

namespace App\Exceptions;

use RuntimeException;

/** A request that is valid input but violates a clinical/business rule. Rendered as a validation-style error. */
class BusinessRuleException extends RuntimeException
{
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
