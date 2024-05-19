
# AuditLog - Trackable Trait
Whenever you create or update a model instance, the changes will be automatically logged
The `created_by` will be set, and the creation will be logged.
## Creating new model
```php
$post = new Post();
$post->title = 'New Post';
$post->save();
```
## Updating an existing model
The `updated_by` will be set, and the update will be logged with old and new values.
```php
$post->title = 'Updated Post';
$post->save(); 
```