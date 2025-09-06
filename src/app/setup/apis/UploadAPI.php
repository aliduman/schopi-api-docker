<?php

class UploadAPI
{
    public $fileManager;
    public $file_path = '';

    public $request;


    function __construct()
    {
        $uploadDirectory = getcwd() . '/images/';
        $destination = $uploadDirectory;
        $this->fileManager = new File($destination);

    }

    function generateRandomFileName($fileName)
    {
        $ext = md5(uniqid(rand(), true));
        return $ext;
    }

    function prodOrLocalCheck()
    {
        $host = $_SERVER['HTTP_HOST'];
        // OS X'te yerel ortamı tespit etmek için "localhost" stringini kontrol ediyoruz
        if (strpos(PHP_OS, 'Darwin') !== false) {
            $port = ':8888';
        } else {
            $port = '';
        }

        // Yerel ortamı tespit etmek için "localhost" stringini kontrol ediyoruz
        if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
            // Yerel ortam için ayarlar
            $filePath = 'http://localhost'.$port.'/schopi-php-nap-api/images/';
        } else {
            // Prodüksiyon ortamı için ayarlar
            $filePath = 'https://schopi-api-cd92fb2da65b.herokuapp.com/images/';
        }

        return $filePath;

    }

    function upload_image ()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

            if (empty($_FILES['file']['name'])) {
                echo json_encode([
                    'message' => 'No file uploaded',
                    'status'  => false
                ]);
                return;
            }

            $fileName = $this->generateRandomFileName($_FILES['file']['name']);
            $result = $this->fileManager->handle($_FILES['file'], $fileName, true);

            if ($result === true) {
                echo json_encode([
                    'message' => 'File uploaded successfully',
                    'imageUrl' => $this->prodOrLocalCheck() . $this->fileManager->getFileName(),
                    'status' => true
                ]);
            } else {
                echo json_encode([
                    'message' => $result,
                    'status' => false
                ]);
            }
        } else {
            echo json_encode([
                'message' => 'Invalid request or no file upload',
                'status'  => false
            ]);
        }
    }

    function delete_image()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file'])) {
            $file = $_POST['file'];
            $result = $this->fileManager->delete($file);

            if ($result === true) {
                echo json_encode([
                    'message' => 'File deleted successfully',
                    'status' => true
                ]);
            } else {
                echo json_encode([
                    'message' => $result,
                    'status' => false
                ]);
            }
        } else {
            echo json_encode([
                'message' => 'Invalid request or no file upload',
                'status'  => false
            ]);
        }

    }

}

?>