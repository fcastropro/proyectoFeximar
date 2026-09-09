<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Throwable;

trait HandlesRestrictedDeletes
{
    protected function deleteOrFailFriendly(
        callable $delete,
        string $redirectRoute,
        string $successMessage,
        ?array $redirectParameters = null,
    ): RedirectResponse {
        try {
            $delete();
        } catch (QueryException $exception) {
            if ($this->isForeignKeyConstraintViolation($exception)) {
                return redirect()
                    ->route($redirectRoute, $redirectParameters ?? [])
                    ->with(
                        'error',
                        'No se puede eliminar este registro porque tiene información relacionada.'
                    );
            }

            throw $exception;
        }

        return redirect()
            ->route($redirectRoute, $redirectParameters ?? [])
            ->with('success', $successMessage);
    }

    protected function isForeignKeyConstraintViolation(Throwable $exception): bool
    {
        $sqlState = (string) $exception->getCode();
        $message = $exception->getMessage();

        return $sqlState === '23000'
            || str_contains($message, 'Integrity constraint violation')
            || str_contains($message, 'foreign key constraint')
            || str_contains($message, 'FOREIGN KEY constraint failed');
    }
}
