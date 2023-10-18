#	lib_easyredmine_api
-	Joomla library for using [EasyRedmine](https://www.easyredmine.com) / [Redmine](http://www.redmine.org) REST API to create or manage issues, projects and many more from within Joomla! extensions.

## OBSOLETE / DEPRECATED / NOT MAINTAINED => GOTO: 
-   https://www.easyredmine.com/resources/rest-api
-   https://app.swaggerhub.com/apis-docs/easysoftware/EasyProject/ 

##	About
-	Author:    Easy Software s.r.o. <info@easysoftware.cz>
-	License:   http://opensource.org/licenses/GPL-3.0 GPL-3.0
-	Copyright: 2015 Easy Software s.r.o.

##  Requirements
-   SimpleXML extension installed and enabled in PHP

##	Basic usage

### Importing library
```php
if (!jimport('easyredmine_api.rest'))
{
	die('Missing EasyRedmine Rest Api library');
}
```

### Getting API instance
```php
$api = EasyRedmineRestApi::getInstance('issues', 'https://example.com', 'XXXXXXXXXXX');
```

### Get list of issues
```php
$filters = array('assigned_to_id' => 27);
$list    = $api->getList($filters)

if ($list !== false)
{
	print_r($list);
}
else
{
	echo 'Error occurred, message = ' . $api->getError();
}
```

### Get detail of issue
```php
$id    = 123;
$issue = $api->get($id)

if ($issue !== false)
{
	print_r($issue);
}
else
{
	echo 'Error occurred, message = ' . $api->getError();
}
```

###	Create / Update issue
```php
$issue = (object) array(
	//if 'id' property was set, then UPDATE would be done instead of INSERT
	'subject'    => 'testing issues',
	'project_id' => 123,
);

if ($api->store($issue))
{
	echo 'Success, stored issue id  = ' . $issue->id;
}
else
{
	echo 'Error occurred, message = ' . $api->getError();
}
```

### Delete issue
```php
$id = 123;

if ($api->delete($id))
{
	echo 'Success, issue was deleted id  = ' . $id;
}
else
{
	echo 'Error occurred, message = ' . $api->getError();
}
```
