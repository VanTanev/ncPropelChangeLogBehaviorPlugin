ncPropelChangeLogBehaviorPlugin
===============================

The `ncPropelChangeLogBehaviorPlugin` provides a Behavior for Propel objects that allows you to track any changes made to them. You can use that later for auditing or if you want to keep a timeline of the changes.

Installation
------------

  * Install the plugin

        $ symfony plugin:install ncPropelChangeLogBehaviorPlugin
        $ symfony plugin:publish-asset

  * Rebuild your model and update your database

        $ symfony propel:build --model

  * Clear the cache

        $ symfony cache:clear

Usage
-----

The two main methods that the behavior adds to the model are:

  * **getChangeLog()**

    ```php
    <?php $object->getChangeLog(Criteria $criteria = null, $transformToAdapters = true, PropelPDO $con = null)
    ```

      This method retrieves the complete changelog for a particular object.
      You can use the `$criteria` variable to filter the changes returned, for example:

      ```php
      <?php

      // filter by date of change
      $c = new Criteria();
      $c->add(ncChageLogEntryPeer::CREATED_AT, strtotime('-2 weeks'));
      $c->addAnd(ncChangeLogEntryPeer::CREATED_AT, strtotime('-1 week'));

      $changelog = $object->getChangeLog($c);


      // filter by username
      $c = new Criteria();
      $c->add(ncChangeLogEntryPeer::USERNAME, 'vagabond');

      $changelog = $object->getChangeLog($c);


      // filter by operation. Available operations:
      // ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_INSERTION
      // ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_UPDATE
      // ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_DELETION
      // ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_CUSTOM_MESSAGE

      $c = new Criteria();
      $c->add(ncChangeLogEntryPeer::OPERATION_TYPE, ncChangeLogEntryOperation::NC_CHANGE_LOG_ENTRY_OPERATION_CUSTOM_MESSAGE);

      // this will return only the changelog entries created with
      // $object->setCustomMessage($changelog_message)
      $changelog = $object->getChangeLog($c);
      ```

  * **getRelatedChangeLog()**

    ```php
    <?php $object->getRelatedChangeLog($filter = null, $transformToAdapters = true, PropelPDO $con = null)
    ```

      This method will retrieve the changelogs for any related objects that have the `ncChangeLogBehavior` set.
      The result is an array in the form of:

      ```php
      <?php
      $changelog = array(
        'relatedObjectName' => $changelog_of_related_object,
        // ...
      );
      ```

      **Notice:** This method will ignore M:M relations in Propel < 1.5

  * The `transformToAdapters` variable denotes if the result of the metods will be
    an array of `ncChangeLogAdapter` objects or an array of plain `ncChangeLogEntry` objects.
    `ncChangeLogAdapter` objects are specially created to represent the differen
    changelog operations(insert, update, delete, custom). Normally you will never need
    to use the plain `ncChangeLogEntry` objects, unless you are trying to hack some sort of custom functionality.


Setup
-----

To use this behavior, Propel behaviors must be activated in your model, and you need to add the Behavior code at the end of all the classes of your model that need a change log. To do so, simply add the following lines to your model classes (normally under your project's lib/model/ directory):

```php
<?php
// in lib/model/MyModelClass.php
class MyModelClass extends BaseMyModelClass
{
  // ...
}

// add the behavior only for apps, disable in CLI and test env
if (sfContext::hasInstance() && sfConfig::get('sf_environment') != 'test')
{
  sfPropelBehavior::add('MyModelClass', array('changelog'));
}
```

After this clean your cache and you're done!


Change Log presentation
-----------------------

The plugin provides the `ncChangeLogEntry` module which presents a very simple but fully functional interface for you to show any object's change log.

To use this module you must enable it in your app's settings.yml:

```yaml
all:
  .settings:
    ## add 'ncChangeLogEntry' to your already enabled modules
    enabled_modules:        [default, ncChangeLogEntry]
```

Two routes are defined:

  * `nc_change_log`: the list of change log entries for a specific object. Takes two parameters: 'class' (the string name of the class) and 'pk' (the object's primary key).

  * `nc_change_log_detail`: the detail for a specific change log entry. Takes one parameter: 'id' (the primary key of the ncChangeLogEntry object).

You can easily link to a specific object's changelog with the following helper function:

```php
<?php
$object = new MyModelClass();

echo link_to("The object's changelog", ncChangeLogUtils::getChangeLogRoute($object));
```


Further configuration
---------------------

```yaml
all:
  nc_change_log_behavior:
    ## sfUser method used when obtaining the 'username' of the person performing the changes.
    username_method:              getUsername
    ## 'username' value used when running a task from cli that registers changes in the model.
    username_cli:                 cli
    ## ncChangeLogEntryFormatter child class used when formatting the text
    formatter_class:              ncChangeLogEntryFormatter
    ## if this setting is true, the plugin will fire events for field value formatting; check the *Fields formatting* section below
    fire_formatting_events:       true
    ## Instance method used when trying to translate the class name for an object. It's ok if the method does not exist
    object_translation_method:    getHumanName
    ## Instance method used when trying to translate a field name for an object. It's ok if the method does not exist
    field_translation_method:     translateField
    ## If this is set to true, the messages will be translated using the catalogue app_name/i18n/tables/table_name. Only valid when using the default translation methods.
    translation_use_i18n:         false
    ## The date format used when retrieving the changes for a date field
    date_format:                  'Y/m/d'
    ## The datetime format used when retrieving the changes for a datetime field
    date_time_format:             'Y/m/d H:i:s'
    ## The time format used when retrieving the changes for a time field
    time_format:                  'H:i:s'
    ## If set to true, values will be unescaped. Use this if you have a formatter that returns javascript or html
    unescape_values:              false
    ## For foreign keys, this setting will make the plugin retrieve the foreign object and use its __toString() representation.
    get_foreign_values:           false
    ## Register routes for a simple interface to view changelogs
    routes_register:              true

    ## Fields that should be ignored when looking for changes in an object...
    ignore_fields:
        ## ...for all classes; These are the actual default values, feel free to add more that apply to your needs
        any_class: [created_at, created_by, updated_at, updated_by]
        ## ...for MySpecialClass and so on... (this is just an example, not a default value)
        MySpecialClass: [name, height]
```

Custom formatter
----------------

The configured formatter class (which must be a subclass of `ncChangeLogEntryFormatter`) is used when showing the details of a change log entry.

You can extend this class and define your custom format strings, simply by overriding its instance protected variables. After doing so, just change the configuration value (see above) regarding the formatter class and the details will be formatted using your new class.

See the `ncChangeLogEntryFormatter` class for further information.


Custom fields formatting
-----------------

Whenever a field is rendered, and `fire_formatting_events` option is set to `true`, a filter event is fired for that particular field.
The event name follows the template "*TABLE_NAME*.render_*FIELD_NAME*", and the value of the field is sent as a parameter.
This is very useful when you want to render foreign keys or constants. Example use:

We have a table named **summary** that has an integer field named `current_state`.

```php
<?php
## in /apps/<application>/config/<application>Configuration.class.php

$this->dispatcher->connect('summary.render_current_state', array('SummaryPeer', 'renderCurrentStateField'));
```

Now in the *Summary* peer class we will associate the integer values with a string.

```php
<?php
// in lib/model/SummaryPeer.php

class SummaryPeer extends BaseSummaryPeer
{
  /**
   * This method defines a custom rendering for the current_state column
   * The current state column is an integer which corresponds to a particular state
   */
  public static function renderCurrentStateField($event, $value)
  {
    switch ($value)
    {
      case 1:
        $string_representation = 'New';
        break;
      case 2:
        $string_representation = 'Old';
        break;
      default:
        $string_representation = 'Invalid State';
    }

    return $string_representation;
  }

}
```

If you intend to return html or javascript code, you should enable the `unescape_values` option in *app.yml*.


Changelog customization
-----------------------

The ncChangeLogBehavior will fire an event before a new changelog entry is written to the database,
allowing you to modify it. The name of the event is "*TABLE_NAME*.nc_filter_changes".
The subject of the event is the Propel object, and the value to be filtered is the changes, in the following format:

```php
<?php

array(
  'changes' => array(
    'NAME_OF_CHANGED_COLUMN' => array(
      'field' => # the name of the field, will be used when displaying the changelog
      'type'  => # the column type for the field, so that you don't have to go use the table map to get it
      'old'   => # old value of the field
      'new'   => # new value of the field
      'raw'   => array(
        'old'   => # raw old value
        'new'   => # raw new value
      )
    ),
    // ...
  )
)
```


Foreign keys
------------

When a table has a foreign key, the plugin will try to find the related object and convert it to string.
If impossible, the value of the foreign key will be shown.
For the string representation to be shown, the `get_foreign_values` option has to be activated in *app.yml*.
