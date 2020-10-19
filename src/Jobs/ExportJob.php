<?php

declare(strict_types=1);

namespace RabbitCMS\Carrot\Jobs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RabbitCMS\Backend\Entities\User;
use RabbitCMS\Carrot\Contracts\QueryHandlerInterface;

abstract class ExportJob implements QueryHandlerInterface
{
    protected User $user;

    protected array $additional = [];

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    abstract protected function cells(): array;

    protected function with(): array
    {
        return [];
    }

    protected function label(): string
    {
        return Str::title(Str::snake(class_basename(get_class($this)), ' '));
    }

    public function handle(QueryJob $job): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $cellIndex = 1;
        $rowIndex = 1;
        $cells = $this->cells();

        foreach ($cells as $key => $cell) {
            $sheet->getCellByColumnAndRow($cellIndex, $rowIndex)
                ->setDataType(DataType::TYPE_STRING)
                ->setValue($key);

            $sheet->getColumnDimensionByColumn($cellIndex)
                ->setAutoSize(true);

            $cellIndex++;
        }

        $job->each(fn(Model $model, int $index) => $this->handleRow($sheet, $model, $index + 2, $cells), $this->with());

        foreach ($this->additional as $value) {
            $sheet->getCellByColumnAndRow($cellIndex, $rowIndex)
                ->setDataType(DataType::TYPE_STRING)
                ->setValue($value);

            $sheet->getColumnDimensionByColumn($cellIndex)
                ->setAutoSize(true);

            $cellIndex++;
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $tmp = tempnam(storage_path('tmp'), 'xlsx');
        $writer->save($tmp);
        try {
            Mail::send((new class() extends Mailable {
                public function build()
                {
                }
            })
                ->subject($label = $this->label())
                ->to($this->user->email, $this->user->name)
                ->html("<b>{$label}</b>")
                ->attach($tmp, [
                    'as' => 'export.xlsx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]));
        } finally {
            unlink($tmp);
        }
    }

    protected function handleRow(Worksheet $sheet, Model $model, int $rowIndex, array $cells = null): void
    {
        $cellIndex = 0;
        foreach ($cells ?? $this->cells() as $callback) {
            $cellIndex++;
            $cell = $sheet->getCellByColumnAndRow($cellIndex, $rowIndex);
            $value = $callback($model, $cell);

            if ($value !== null) {
                $cell->setValue($value)->setDataType(DataType::TYPE_STRING);
            }
        }
        foreach ($this->additional($model) as $property => $value) {
            $cellShift = array_search($property, $this->additional);
            if ($cellShift === false) {
                $cellShift = array_push($this->additional, $property) - 1;
            }
            $cell = $sheet->getCellByColumnAndRow($cellIndex + $cellShift + 1, $rowIndex);
            if ($value !== null) {
                $cell->setValue($value)->setDataType(DataType::TYPE_STRING);
            }
        }
    }

    protected function additional(Model $model): array
    {
        return [];
    }
}
