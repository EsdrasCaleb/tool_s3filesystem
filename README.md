# tool_s3filesystem
Simple plugin to moodle to use an alternative filesystem conected to S3 (works with minIO)

to work put in your config.php
```php
$CFG->alternative_file_system_class = '\tool_s3filesystem\s3_object_file_system';
```