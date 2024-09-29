<?php

namespace App\Models;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileSystem extends File {
    private $request = null;

    public static function new(Request $request) {
        $instance = new static;
        $instance->request = $request;
        return $instance;
    }

    private function createRecord($src, $place = 'mixed') {
        $model = static::select()
            ->where('src', '=', $src)
            ->where('place', '=', $place)
            ->first();
        
        if(!is_null($model))
            return $model->id;

        $model = new static;

        $model->fill(compact('src', 'place'));

        return $model->save()
            ? $model->id
            : abort(500, "Error while saving file");
    }

    public function uploadCustom($name, $dir, $rename = null) {
        $extension = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
        $fileName  = $rename ?? pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME);

        $file = $this->request->file($name, null) 
             ?? abort(400, "File $name not sended");

        try {
            $file->move('files', "$fileName.$extension");
        }
        catch(FileException $e) {
            abort(500, "File $name cannot be uploaded in server");
        }

        return $this->createRecord("/files/$fileName.$extension", $dir);
    }

    public function uploadImage($name, $rename = null) {
        $extension = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
        $fileName  = $rename ?? pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME);

        $file = $this->request->file($name, null) 
            ?? abort(400, "File $name not sended");

        try {
            $file->move('files', "$fileName.$extension");
        }
        catch(FileException $e) {
            abort(500, "File $name cannot be uploaded in server");
        }

        return $this->createRecord("/files/$fileName.$extension", "images");
    }

    public function uploadDocument($name, $rename = null) {
        $extension = pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION);
        $fileName  = $rename ?? pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME);

        $file = $this->request->file($name, null) 
            ?? abort(400, "File $name not sended");

        try {
            $file->move('files', "$fileName.$extension");
        }
        catch(FileException $e) {
            abort(500, "File $name cannot be uploaded in server");
        }

        return $this->createRecord("/files/$fileName.$extension", "document");
    }

    public function batchUploadImages() {
        foreach($_FILES as $key => $file) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName  = pathinfo($file['name'], PATHINFO_FILENAME);

            $file = $this->request->file($key, null) 
                ?? abort(400, "File $key not sended");

            try {
                $file->move('files', "$fileName.$extension");
            }
            catch(FileException $e) {
                abort(500, "File $key cannot be uploaded in server");
            }

            $this->createRecord("/files/$fileName.$extension", "images");
        }
    }

    public function batchUploadDocuments() {
        foreach($_FILES as $key => $file) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName  = pathinfo($file['name'], PATHINFO_FILENAME);

            $file = $this->request->file($key, null) 
                ?? abort(400, "File $key not sended");

            try {
                $file->move('files', "$fileName.$extension");
            }
            catch(FileException $e) {
                abort(500, "File $key cannot be uploaded in server");
            }

            $this->createRecord("/files/$fileName.$extension", "document");
        }
    }
}