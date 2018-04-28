<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * CakePHP3 S3Client Component
 *            with
 *      AWS SDK for PHP3
 * @see https://aws.amazon.com/jp/sdk-for-php/
 * @see https://github.com/aws/aws-sdk-php
 */
class S3ClientComponent extends Component
{
    protected $_defaultConfig = [];

    protected $default_bucket;

    public function initialize(array $config)
    {
        $this->s3 = S3Client::factory([
            'credentials' => [
                'key' => env('AWS_S3_KEY', ''),
                'secret' => env('AWS_S3_SECRET', ''),
            ],
            'region' => env('AWS_S3_REGION', ''),
            'version' => 'latest',
        ]);

        $this->default_bucket = env('AWS_S3_BUCKET', '');
    }

    /**
     * Get a list of files
     * @param string $bucket_name
     * @param string $dir
     * @param int $get_max
     * @return array
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.S3.S3Client.html#_listObjects
     */
    public function getList($bucket_name=null, $dir=null, $get_max=100)
    {
        try {
            if(!$bucket_name) $bucket_name = $this->default_bucket;
            $list_obj = $this->s3->listObjects([
                'Bucket' => $bucket_name,
                'MaxKeys' => $get_max,
                'Prefix' => $dir
            ]);

            foreach ($list_obj['Contents'] as $file) {
                if (mb_substr($file['Key'], -1) !== "/" && (!$dir  || ($dir && strpos($file['Key'], sprintf('%s/', $dir)) !== false))) {
                    $result[] = $file['Key'];
                }
            }

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Uploading files
     * @param string $file_path
     * @param string $store_path
     * @param string $bucket_name
     * @return mixed
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
     */
    public function putFile($file_path, $store_path, $bucket_name=null)
    {
        try {
            if(!$bucket_name) $bucket_name = $this->default_bucket;
            $result = $this->s3->putObject(array(
                'Bucket'       => $bucket_name,
                'Key'          => $store_path,
                'SourceFile'   => $file_path,
            ));

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * File download
     * @param string $s3_file_path
     * @param string $store_dir_path
     * @param string $bucket_name
     * @return mixed
     * @see https://docs.aws.amazon.com/ja_jp/AmazonS3/latest/dev/RetrieveObjSingleOpPHP.html
     */
    public function getFile($s3_file_path, $store_file_path, $bucket_name=null)
    {
        try {
            if(!$bucket_name) $bucket_name = $this->default_bucket;
            $result = $this->s3->getObject([
                'Bucket' => $bucket_name,
                'Key'    => $s3_file_path,
                'SaveAs' => $store_file_path
            ]);

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Directory download (Recursive download)
     * @param string $s3_dir_path
     * @param string $local_dir_path
     * @param string $bucket_name
     */
    public function getDirectory($s3_dir_path, $local_dir_path, $bucket_name=null)
    {
        try {
            if(!$bucket_name) $bucket_name = $this->default_bucket;
            $file_list = $this->getList($bucket_name, $s3_dir_path);
            $this->chkDirHandle($file_list, $local_dir_path);
            foreach ($file_list as $from_path) {
                $to_path = sprintf('%s/%s', $local_dir_path, $from_path);
                $this->getFile($from_path, $to_path);
            }
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Copying files
     * @param string $s3_file_path
     * @param string $s3_copy_file_path
     * @param string $bucket_name_from
     * @param string $bucket_name_to
     * @return mixed
     * @see https://docs.aws.amazon.com/ja_jp/AmazonS3/latest/dev/CopyingObjectUsingPHP.html
     */
    public function copyFile($s3_file_path, $s3_copy_file_path, $bucket_name_from=null, $bucket_name_to=null)
    {
        try {
            if(!$bucket_name_from) $bucket_name_from = $this->default_bucket;
            if(!$bucket_name_to) $bucket_name_to = $this->default_bucket;
            $result = $this->s3->copyObject(array(
                'Bucket'     => $bucket_name_to,
                'Key'        => $s3_copy_file_path,
                'CopySource' => sprintf('%s/%s', $bucket_name_from, $s3_file_path),
            ));

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Copy directory (Recursive copy)
     * @param string $s3_from_dir
     * @param string $s3_to_dir
     * @param string $bucket_name_from
     * @param string $bucket_name_to
     */
    public function copyDirectory($s3_from_dir, $s3_to_dir, $bucket_name_from=null, $bucket_name_to=null)
    {
        try {
            if(!$bucket_name_from) $bucket_name_from = $this->default_bucket;
            if(!$bucket_name_to) $bucket_name_to = $this->default_bucket;
            $file_list = $this->getList($bucket_name_from, $s3_from_dir);

            foreach ($file_list as $from_path) {
                $to_path = sprintf('%s/%s', $s3_to_dir, basename($from_path));
                $this->copyFile($from_path, $to_path);
            }
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Moving files
     * @param $s3_from_path
     * @param $s3_to_path
     * @return bool|mixed
     */
    public function moveFile($s3_from_path, $s3_to_path)
    {
        try {
            $result = false;
            if($this->copyFile($s3_from_path, $s3_to_path)) {
                $result = $this->deleteFile($s3_from_path);
            }

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Move directory (Recursive movement)
     * @param string $s3_from_dir
     * @param string $s3_to_dir
     * @param string null $bucket_name
     */
    public function moveDirectory($s3_from_dir, $s3_to_dir, $bucket_name=null)
    {
        try {
            if(!$bucket_name) $bucket_name = $this->default_bucket;
            $file_list = $this->getList($bucket_name, $s3_from_dir);
            foreach ($file_list as $from_path) {
                $to_path = sprintf('%s/%s', $s3_to_dir, basename($from_path));
                $this->moveFile($from_path, $to_path);
            }
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Delete files
     * @param string $file_path
     * @param string $bucket_name
     * @return mixed
     * @see https://docs.aws.amazon.com/ja_jp/AmazonS3/latest/dev/DeletingMultipleObjectsUsingPHPSDK.html
     */
    public function deleteFile($file_path, $bucket_name=null)
    {
        try {
            if(!$bucket_name) $bucket_name = $this->default_bucket;
            $result = $this->s3->deleteObject(array(
                'Bucket' => $bucket_name,
                'Key'    => $file_path
            ));

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Delete directory (Remove recursively)
     * @param string $dir_name
     * @param string $bucket_name
     * @return mixed
     * @see https://docs.aws.amazon.com/ja_jp/AmazonS3/latest/dev/DeletingMultipleObjectsUsingPHPSDK.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#deleteobjects
     */
    public function deleteDirectory($dir_name, $bucket_name=null)
    {
        try {
            if(!$bucket_name) $bucket_name = $this->default_bucket;
            $file_list = $this->getList($bucket_name, $dir_name);
            $files = $this->createArrayMultipleObjects($file_list);

            $result = $this->s3->deleteObjects(array(
                'Bucket'  => $bucket_name,
                'Delete' => [
                    'Objects' => $files
                ]
            ));

            return $result;
        } catch (S3Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Convert from file list to array for multiple objects
     * @param $file_list
     * @return array
     */
    private function createArrayMultipleObjects($file_list)
    {
        foreach ($file_list as $name) {
            $files[] = array('Key' => $name);
        }
        return $files;
    }

    /**
     * Recursively check the path directories in the file list
     * @param $file_list
     */
    private function chkDirHandle($file_list, $local_dir_path)
    {
        foreach ($file_list as $filename) {
            $local_path = sprintf('%s/%s', $local_dir_path, dirname($filename));
            $this->chkDir($local_path);
        }
    }
    /**
     * Confirm existence of directory and create it if it does not exist.
     * @param $local_dir_path
     */
    private function chkDir($local_dir_path)
    {
        if(!file_exists($local_dir_path)) {
            mkdir($local_dir_path, 0777, true);
        }
    }
}
