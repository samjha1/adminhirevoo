<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

trait HandlesAssignmentFailures
{
    /**
     * @param  callable(): void  $action
     */
    protected function assignmentRedirect(callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();

            return back()->with('success', $successMessage);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (AuthorizationException) {
            return back()->with('error', 'You are not allowed to perform this action.');
        }
    }
}
