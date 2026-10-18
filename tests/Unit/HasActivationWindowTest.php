<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\ActivationWindowFixture;
use Tests\TestCase;

/**
 * The activation window is shared by models whose column names differ, and by a
 * model that has no activation marker column at all.
 */
class HasActivationWindowTest extends TestCase
{
    public function test_the_activation_column_is_part_of_the_window_clause(): void
    {
        $sql = $this
            ->windowedModel('activated_at')
            ->newQuery()
            ->currentlyActive()
            ->toSql();

        static::assertStringContainsString('activated_at', $sql);
        static::assertStringContainsString('starts_at', $sql);
        static::assertStringContainsString('expires_at', $sql);
    }

    public function test_a_model_without_an_activation_column_omits_the_clause(): void
    {
        $sql = $this
            ->windowedModel(null)
            ->newQuery()
            ->currentlyActive()
            ->toSql();

        // No activation marker means the window alone decides: the scope must
        // not mention a column the model does not have.
        static::assertStringNotContainsString('activated_at', $sql);
        static::assertStringContainsString('starts_at', $sql);
        static::assertStringContainsString('expires_at', $sql);
    }

    /**
     * A model using the trait, with the given activation marker column (or none).
     */
    private function windowedModel(?string $activationColumn): ActivationWindowFixture
    {
        $model = new ActivationWindowFixture;

        $model->activationColumnName = $activationColumn;

        return $model;
    }
}
