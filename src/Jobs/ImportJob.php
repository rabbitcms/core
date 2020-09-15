<?php

declare(strict_types=1);

namespace RabbitCMS\Carrot\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\{Dispatchable, PendingDispatch};
use Illuminate\Mail\Mailable;
use RabbitCMS\Carrot\Contracts\ImportInterface;
use RabbitCMS\Carrot\Support\Importer;
use RabbitCMS\Modules\Concerns\BelongsToModule;
use RuntimeException;
use SplFileInfo;

/**
 * Class ImportJob
 *
 * @method static PendingDispatch dispatch(ImportInterface $importClass, SplFileInfo $file, string $encoding = 'utf-8', string $delimiter = ',')
 */
class ImportJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable;
    use BelongsToModule;

    protected string $path;

    protected string $encoding;

    protected string $delimiter;

    protected ?string $email = null;

    protected ImportInterface $job;

    public function __construct(
        ImportInterface $job,
        SplFileInfo $file,
        string $encoding = 'utf-8',
        string $delimiter = ','
    )
    {
        $this->job = $job;
        $this->path = $file->getPathname();
        $this->encoding = $encoding;
        $this->delimiter = $delimiter;
    }

    public function handle(Mailer $mailer): array
    {
        $file = new SplFileInfo($this->path);
        $importer = new Importer($file, $this->encoding, $this->delimiter);
        try {
            $log = $importer->handle($this->job);
        } catch (Exception $exception) {
            $log = $importer->getLog();
        }
        $class = basename(str_replace('\\', '/', get_class($this->job)));
        if ($this->email) {
            $mailer->send((new class() extends Mailable {
                public function build(): void
                {
                }
            })
                ->to($this->email)
                ->subject("Import report: {$class}")
                ->html("<b>{$class}</b>")
                ->attachData(implode("\n", array_map(static function (array $log) {
                    $level = strtoupper($log['level']);
                    $message = str_replace('"', '""', $log['message']);
                    $context = empty($log['context']) ? '' : str_replace('"', '""',
                        json_encode($log['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    return "\"{$log['line']}\";\"{$level}\";\"{$message}\";\"{$context}\"";
                }, $log)), 'report.csv')
                ->attach($file->getPathname(), ['as' => 'source.csv'])
            );
        }

        try {
            if (isset($exception)) {
                throw $exception;
            }
        } finally {
            @unlink($file->getPathname());
        }


        return $log;
    }

    public function reportTo(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
