<?php

namespace App\Models;

use App\Models\Global\Response;
use Illuminate\Http\Request;
use Str;

class FileSystem extends File {
    private $request = null;

    public static function new(Request $request) {
        $instance = new static;
        $instance->request = $request;
        return $instance;
    }

    private function compress($source, $destination, $quality) {
        $info = \getimagesize($source);
    
        if(is_bool($info))
            return false;

        $image = null;
        switch($info['mime']) {
            case 'image/jpeg':
                $image = \imagecreatefromjpeg($source);
                $destination = \str_replace(".jpeg", ".jpg", $destination);
                break;
            case 'image/gif':
                $image = \imagecreatefromgif($source);
                $destination = \str_replace(".gif", ".jpg", $destination);
                break;
            case 'image/png':
                $image = \imagecreatefrompng($source);
                $destination = \str_replace(".png", ".jpg", $destination);
                break;
            default:
                return false;
        }

        $result = \imagejpeg($image, $destination, $quality);
        \imagedestroy($image);
        return $result;
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

    public static function validateFile($name, $mime = 'image/', $size = '10M') {
        $fileSize = static::strToSize($size);

        $type = mime_content_type($_FILES[$name]['tmp_name']) ?? '';
        $size = filesize($_FILES[$name]['tmp_name']) ?? -1;

        if(!str_starts_with($type, $mime))
            Response::badRequest("$name имеет не поддерживаемый тип данных", true);
        else if($size == -1 || $size > $fileSize)
            Response::badRequest("$name больше $size", true);
    }

    public static function strToSize($val = '10M') {
        $result = intval(substr($val, 0, -1));
        $val = strtolower($val);

        switch($val[strlen($val) - 1]) {
            case 'b':
                return $result;
            case 'k':
                return $result * 1024;
            case 'm':
                return $result * 1024 * 1024;
            case 'g':
                return $result * 1024 * 1024;
        }
        
        return intval($val);
    }
    
    public function uploadCustom($name, $dir, $rename = null) {
        $extension = strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
        $fileName  = $rename ?? pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME);

        $path = base_path("/public/files/$fileName.$extension");
        if(!move_uploaded_file($_FILES[$name]['tmp_name'], $path))
            abort(500, "File $name cannot be uploaded in server");

        if($this->compress($path, $path, 100)) {
            if($extension != 'jpg')
                unlink($path);
            $extension = 'jpg';
        }

        return $this->createRecord("/files/$fileName.$extension", $dir);
    }

    public function uploadImage($name, $rename = null) {
        $extension = strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
        $fileName  = $rename ?? pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME);

        $path = base_path("/public/files/$fileName.$extension");
        if(!move_uploaded_file($_FILES[$name]['tmp_name'], $path))
            abort(500, "File $name cannot be uploaded in server");
        
        if($this->compress($path, $path, 100)) {
            if($extension != 'jpg')
                unlink($path);
            $extension = 'jpg';
        }

        return $this->createRecord("/files/$fileName.$extension", "images");
    }

    public function uploadDocument($name, $rename = null) {
        $extension = strtolower(pathinfo($_FILES[$name]['name'], PATHINFO_EXTENSION));
        $fileName  = $rename ?? pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME);

        $path = base_path("/public/files/$fileName.$extension");
        if(!move_uploaded_file($_FILES[$name]['tmp_name'], $path))
            abort(500, "File $name cannot be uploaded in server");

        if($this->compress($path, $path, 100)) {
            if($extension != 'jpg')
                unlink($path);
            $extension = 'jpg';
        }

        return $this->createRecord("/files/$fileName.$extension", "document");
    }

    public function batchUploadImages($place = '') {
        $imageSizeLimit = static::strToSize('20M');

        foreach($_FILES as $key => $file) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileName  = pathinfo($file['name'], PATHINFO_FILENAME);

            $size = filesize($file['tmp_name']) ?? -1;
            if(is_bool($size))
                abort(500, "File $key cannot be checked in server");

            if($imageSizeLimit < $size)
                abort(500, "File $key too big");

            $path = base_path("/public/files/$fileName.$extension");
            if(!move_uploaded_file($file['tmp_name'], $path))
                abort(500, "File $key cannot be uploaded in server");

            if($this->compress($path, $path, 100)) {
                if($extension != 'jpg')
                    unlink($path);
                $extension = 'jpg';
            }

            $this->createRecord("/files/$fileName.$extension", "images");
        }
    }

    public function batchUploadDocuments($place = null) {
        $docSizeLimit = static::strToSize('40M');

        foreach($_FILES as $key => $file) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileName  = pathinfo($file['name'], PATHINFO_FILENAME);

            $size = filesize($file['tmp_name']) ?? -1;
            if(is_bool($size))
                abort(500, "File $key cannot be checked in server");

            if($docSizeLimit < $size)
                abort(500, "File $key too big");

            $path = base_path("/public/files/$fileName.$extension");
            if(!move_uploaded_file($file['tmp_name'], $path))
                abort(500, "File $key cannot be uploaded in server");

            if($this->compress($path, $path, 70)) {
                if($extension != 'jpg')
                    unlink($path);
                $extension = 'jpg';
            }

            $place = isset($place)
                ? "document/$place"
                : "document";

            $this->createRecord("/files/$fileName.$extension", $place);
        }
    }
}