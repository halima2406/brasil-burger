<?php

namespace App\Service\Impl;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploaderService
{
    private string $uploadsDirectory;

    public function __construct(string $uploadsDirectory)
    {
        $this->uploadsDirectory = $uploadsDirectory;
    }

    public function upload(UploadedFile $file): string
    {
        $fileName = $this->cleanFilename($file->getClientOriginalName()) . '.' . $file->guessExtension();

        $file->move($this->uploadsDirectory, $fileName);

        return $fileName;
    }

    public function getUploadsDirectory(): string
    {
        return $this->uploadsDirectory;
    }

    private function cleanFilename(string $filename): string
    {
        return 'file-' . time();
    }

    public function deleteFile(string $filename): bool
    {
        $filepath = $this->uploadsDirectory . '/' . $filename;
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
}