<?php
namespace App\Controller;

use App\Controller\AppController;

class S3Controller extends AppController
{
    protected $storage_path;
    
    public function initialize()
    {
        parent::initialize();
        $this->loadComponent('S3Client');
        $this->autoRender = false;

        $this->storage_path = STORAGE_PATH;
    }

    public function getList()
    {
        $file_list = $this->S3Client->getList(null);
        print_r($file_list); exit;
    }

    public function upload()
    {
        $file_name = "test.txt";
        $store_dir = "ddd/";

        $file_local_path = sprintf('%s/%s', $this->storage_path, $file_name);
        $file_store_path = sprintf('%s%s', $store_dir, $file_name);

        $result = $this->S3Client->putFile($file_local_path, $file_store_path);
    }

    public function download()
    {
        $file_name = "test.png";
        $s3_dir = "";
        $store_dir = sprintf('%s/d', $this->storage_path);

        $s3_file_path = sprintf('%s%s', $s3_dir, $file_name);
        $store_file_path = sprintf('%s/%s', $store_dir, $file_name);

        $file_obj = $this->S3Client->getFile($s3_file_path, $store_file_path);
    }

    public function downloadDirectory()
    {
        $s3_dir = "cp";
        $local_dir = "dl";
        $local_dir_path = sprintf('%s/%s', $this->storage_path, $local_dir);
        $this->S3Client->getDirectory($s3_dir, $local_dir_path);
    }

    public function copy()
    {
        $file_name = "test.png";
        $s3_dir = "";
        $s3_copy_dir = "cp/";

        $s3_file_path = sprintf('%s%s', $s3_dir, $file_name);
        $s3_copy_file_path = sprintf('%s%s', $s3_copy_dir, $file_name);

        $this->S3Client->copyFile($s3_file_path, $s3_copy_file_path);
    }

    public function copyDirectory()
    {
        $s3_from_dir = "cp";
        $s3_to_dir = "cp_d";
        $this->S3Client->copyDirectory($s3_from_dir, $s3_to_dir);
    }

    public function move()
    {
        $file_name = "test.png";
        $s3_from_dir = "cp/";
        $s3_to_dir = "mv/";
        $s3_from_path = sprintf('%s%s', $s3_from_dir, $file_name);
        $s3_to_path = sprintf('%s%s', $s3_to_dir, $file_name);
        $this->S3Client->moveFile($s3_from_path, $s3_to_path);
    }

    public function moveDirectory()
    {
        $s3_from_dir = "mv";
        $s3_to_dir = "mv_d";
        $this->S3Client->moveDirectory($s3_from_dir, $s3_to_dir);
    }

    public function delete()
    {
        $s3_file_path = 'cp/test.png';
        $this->S3Client->deleteFile($s3_file_path);
    }

    public function deleteDirectory()
    {
        $s3_dir_path = 'cp';
        $this->S3Client->deleteDirectory($s3_dir_path);
    }
}
