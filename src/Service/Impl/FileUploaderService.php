<?php

namespace App\Service\Impl;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploaderService
{
    private string $uploadsDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $uploadsDirectory, SluggerInterface $slugger)
    {
        $this->uploadsDirectory = $uploadsDirectory;
        $this->slugger = $slugger; 
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