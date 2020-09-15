<?php

declare(strict_types=1);

namespace RabbitCMS\Carrot\Support;

use Closure;
use Exception;
use Illuminate\Support\Arr;
use Psr\Log\LoggerTrait;
use RabbitCMS\Carrot\Contracts\{ImporterInterface, ImportInterface};
use Psr\Log\LogLevel;
use RabbitCMS\Carrot\Exceptions\ImportFailRowException;
use RabbitCMS\Modules\Concerns\BelongsToModule;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class Importer implements ImporterInterface
{
    use BelongsToModule;
    use LoggerTrait;

    private SplFileInfo $file;

    private string $encoding;

    private string $delimiter;

    private int $line = 0;

    private ?array $head = null;

    private array $log = [];

    private ?Closure $translator = null;

    private ?array $current = null;

    private bool $parse = false;

    /**
     * Importer constructor.
     *
     * @param  SplFileInfo  $file
     * @param  string  $encoding
     * @param  string  $delimiter
     */
    public function __construct(
        SplFileInfo $file,
        string $encoding = 'utf-8',
        string $delimiter = ','
    )
    {
        $this->file = $file->openFile();
        $this->encoding = $encoding;
        $this->delimiter = $delimiter;
    }

    public function handle(ImportInterface $import): array
    {
        $this->file->rewind();
        $this->line = 0;
        $this->log = [];
        try {
            $import->init($this);
            while ($row = $this->next()) {
                if (count($row) === 1 && $row[0] === '') {
                    continue;
                }
                if ($this->parse) {
                    $data = [];
                    foreach ($this->head as $index => $key) {
                        Arr::set($data, $key, $row[$index] ?? null);
                    }
                    $row = $data;
                }
                try {
                    $import->row($row, $this);
                } catch (ImportFailRowException $exception) {
                }
            }
            $import->end($this);
        } catch (Exception $e) {
            $import->catch($e, $this);
        }

        return $this->log;
    }

    public function head(bool $parse = false): array
    {
        if ($this->head === null) {
            if ($this->line !== 0) {
                throw new RuntimeException('Header already fetched');
            }
            $this->head = $this->next();
            if ($this->head === null) {
                throw new RuntimeException('Empty file');
            }
        }

        $this->parse = $parse;

        return $this->head;
    }

    public function next(): ?array
    {
        if (is_array($this->current)) {
            $current = $this->current;
            $this->current = null;
            $this->line++;

            return $current;
        }
        if ($this->file->eof()) {
            return null;
        }
        $this->line++;

        return array_map(function ($value) {
            return iconv($this->encoding, 'UTF-8//IGNORE', trim((string) $value));
        }, $this->file->fgetcsv($this->delimiter));
    }

    public function probe(Closure $condition): bool
    {
        $line = $this->line;
        $this->current = $this->next();
        $this->line = $line;
        if ($this->current === null) {
            return false;
        }

        return $condition($this->current);
    }

    /**
     * @param  mixed  $level
     * @param  string  $message
     * @param  array  $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->log[] = [
            'level' => $level,
            'line' => $this->line,
            'message' => $this->translate($message),
            'context' => $context,
        ];
    }

    public function log2(string $format, ...$args): void
    {
        $this->log[] = "{$this->line};".sprintf($format, ...$args);
    }

    public function getLog(): array
    {
        return $this->log;
    }

    private function translate(string $key): string
    {
        return $this->translator ? ($this->translator)($key) : $key;
    }

    public function setTranslator(Closure $closure): ImporterInterface
    {
        $this->translator = $closure;

        return $this;
    }

    /**
     * @param  string  $message
     * @param  array  $context
     * @param  Throwable|null  $previous
     * @throws ImportFailRowException
     */
    public function error($message, array $context = [], Throwable $previous = null): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
        throw new ImportFailRowException($message, $context, $previous);
    }
}
