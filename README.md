# Installation

```bash
composer require recca0120/streaming-response
```

# Example

```php
<?php
// routes/web.php
Route::get('streaming', function() {
    return Storage::disk('s3')->streaming('path/to.mp4');
});
```