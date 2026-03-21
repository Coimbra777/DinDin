<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'finance_recurring_transactions';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        if (! Schema::hasColumn($tableName, 'is_fixed')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->boolean('is_fixed')->default(false);
            });
        }

        if (! Schema::hasColumn($tableName, 'installments_total')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedSmallInteger('installments_total')->nullable();
            });
        }

        if (! Schema::hasColumn($tableName, 'installments_paid')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedInteger('installments_paid')->default(0);
            });
        }

        if (! Schema::hasColumn($tableName, 'next_run_date')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->date('next_run_date')->nullable();
            });
        }

        $tz = config('app.timezone');

        DB::table($tableName)->orderBy('id')->chunkById(100, function ($rows) use ($tz, $tableName): void {
            foreach ($rows as $row) {
                if ($row->next_run_date !== null && $row->next_run_date !== '') {
                    continue;
                }
                $next = $this->backfillNextRunDate($row, $tz);
                DB::table($tableName)->where('id', $row->id)->update([
                    'next_run_date' => $next,
                ]);
            }
        });
    }

    public function down(): void
    {
        $tableName = 'finance_recurring_transactions';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        $toDrop = array_values(array_filter(
            ['next_run_date', 'installments_paid', 'installments_total', 'is_fixed'],
            fn (string $col) => Schema::hasColumn($tableName, $col)
        ));

        if ($toDrop === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }

    private function backfillNextRunDate(object $row, string $tz): string
    {
        $start = Carbon::parse($row->start_date, $tz)->startOfDay();
        $last = $row->last_run_at ? Carbon::parse($row->last_run_at, $tz)->startOfDay() : null;

        if ($row->frequency === 'monthly' && $row->day_of_month !== null) {
            $dom = (int) $row->day_of_month;
            $cursor = $last
                ? $last->copy()->addMonth()->startOfMonth()
                : $start->copy()->startOfMonth();

            for ($i = 0; $i < 48; $i++) {
                $dim = $cursor->daysInMonth;
                $d = min($dom, $dim);
                $candidate = $cursor->copy()->day($d)->startOfDay();
                if ($last !== null && $candidate->lte($last)) {
                    $cursor->addMonth();

                    continue;
                }
                if ($candidate->lt($start)) {
                    $cursor->addMonth();

                    continue;
                }
                if ($row->end_date !== null) {
                    $end = Carbon::parse($row->end_date, $tz)->endOfDay();
                    if ($candidate->gt($end)) {
                        break;
                    }
                }

                return $candidate->toDateString();
            }
        }

        if ($row->frequency === 'weekly' && $row->day_of_week !== null) {
            $dow = (int) $row->day_of_week;
            $cursor = $last ? $last->copy()->addDay() : $start->copy();
            for ($i = 0; $i < 400; $i++) {
                if ((int) $cursor->dayOfWeekIso === $dow && $cursor->gte($start) && ($last === null || $cursor->gt($last))) {
                    return $cursor->toDateString();
                }
                $cursor->addDay();
            }
        }

        return $start->toDateString();
    }
};
