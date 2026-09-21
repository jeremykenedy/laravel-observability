<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use RuntimeException;

class EnvironmentFile
{
    public function __construct(protected Application $app, protected Filesystem $files) {}

    public function path(): string
    {
        return $this->app->environmentFilePath();
    }

    public function exists(): bool
    {
        return $this->files->isFile($this->path()) && $this->files->isWritable($this->path());
    }

    public function update(array $values): void
    {
        if (!$this->exists()) {
            throw new RuntimeException('A writable environment file is required. Create it before running this command.');
        }

        $content = $this->files->get($this->path());
        $newline = str_contains($content, "\r\n") ? "\r\n" : "\n";

        foreach ($values as $key => $value) {
            if (!preg_match('/\A[A-Z][A-Z0-9_]*\z/', $key) || strpbrk($value, "\r\n\0") !== false) {
                throw new InvalidArgumentException('Environment values must use a valid key and fit on one line.');
            }

            $encoded = preg_match('/\A[a-zA-Z0-9_.:\/\-]+\z/', $value)
                ? $value
                : '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
            $line = $key.'='.$encoded;
            $pattern = '/^[\t ]*(?:export[\t ]+)?'.preg_quote($key, '/').'[\t ]*=[^\r\n]*/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace_callback($pattern, fn () => $line, $content);
            } else {
                $content = rtrim($content, "\r\n").$newline.$line.$newline;
            }
        }

        $this->files->replace($this->path(), $content, fileperms($this->path()) & 0777);
    }
}
