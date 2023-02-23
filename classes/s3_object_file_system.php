<?php 
namespace tool_s3filesystem;

global $CFG;

require $CFG->dirroot.'/admin/s3filesystem/vendor/autoload.php';
use Aws\S3\S3Client as S3Client;
use Aws\Exception\AwsException as AwsException;


class s3_object_file_system extends object_file_system {

    protected function initialise_external_client($config) {

        $s3client = new client([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'endpoint' => 'https://XXXXXX.stackhero-network.com',
            'use_path_style_endpoint' => true,
            'credentials' => [
              'key'    => 'YOUR_ACCESS_KEY',
              'secret' => 'YOUR_SECRET_KEY'
            ],
        ]);

        return $s3client;
    }

    /**
     * @inheritdoc
     */
    public function readfile(\stored_file $file) {
        $path = $this->get_remote_path_from_storedfile($file);

        $this->get_logger()->start_timing();
        if ($path == $this->get_external_client()->get_fullpath_from_hash($file->get_contenthash())) {
            // There is an issue using core readfile_allow_large() for the big (more than 1G) files from s3.
            $success = readfile($path);
        } else {
            $success = readfile_allow_large($path, $file->get_filesize());
        }
        $this->get_logger()->end_timing();
        $this->get_logger()->log_object_read('readfile', $path, $file->get_filesize());

        if (!$success) {
            manager::update_object_by_hash($file->get_contenthash(), OBJECT_LOCATION_ERROR);
        }
    }

    /**
     * @inheritdoc
     */
    public function copy_from_local_to_external($contenthash) {
        $localpath = $this->get_local_path_from_hash($contenthash);

        try {
            $this->get_external_client()->upload_to_s3($localpath, $contenthash);
            return true;
        } catch (\Exception $e) {
            $this->get_logger()->error_log(
                'ERROR: copy ' . $localpath . ' to ' . $this->get_external_path_from_hash($contenthash) . ': ' . $e->getMessage()
            );
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function supports_presigned_urls() {
        return true;
    }
}