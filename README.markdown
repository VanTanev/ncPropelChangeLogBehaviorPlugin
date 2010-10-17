ncPropelChangeLogBehaviorPlugin
===============================

The `ncPropelChangeLogBehaviorPlugin` provides a Behavior for Propel objects so that any changes made to them are registered and available for later audit or inspection.

Installation
------------

  * Install the plugin

        $ symfony plugin:install ncPropelChangeLogBehaviorPlugin
        
  * Rebuild your model and update your database
  
        $ symfony propel:build-all

  * Clear the cache

        $ symfony cache:clear

Model integration
-----------------

This Behavior adds the following methods to the objects implementing it:

  * *getChangeLog(Criteria $criteria = null, $transformToAdapters = true, PropelPDO $con = null)* - retrieves the whole changelog for a specific object.

  * *getRelatedChangeLog($from_date = null, $transformToAdapters = true, PropelPDO $con = null)* - provides an easy way of obtaining the whole changelog for the related tables of an object.

  * *getChangeLogRoute()* - returns the '@nc_change_log' string to access the object's change log list, with the mandatory parameters already set.

To use this behavior, Propel behaviors must be activated in your model, and you need to add the Behavior code at the end of all the classes of your model that need a change log. To do so, simply add the following lines to your model classes (normally under your project's lib/model/ directory):

    [php]
    <?php
    // in lib/model/MyModelClass.php
    class MyModelClass extends BaseMyModelClass
    {
      // ...
    }
    
    sfPropelBehavior::add('MyModelClass', array('changelog'));

After this clean your cache and you're done!


Change Log presentation
-----------------------

A module is provided with a very simple but fully functional interface for you to show any object's change log.

Two routes are defined:

  * 'nc_change_log': the list of change log entries for a specific object. Takes two parameters: 'class' (the string name of the class) and 'pk' (the object's primary key).

  * 'nc_change_log_detail': the detail for a specific change log entry. Takes one parameter: 'id' (the primary key of the ncChangeLogEntry object).

If you wish to use this module, you'll have to enable it in your application's configuration file:

    [php]
    ## in apps/<application>/config/settings.yml
    all:
      .settings:
        ## add 'ncchangelogentry' to your already enabled modules:
        enabled_modules: [default, ncchangelogentry]


Further configuration
---------------------

The plugin uses some configuration values, that can be overridden in your app.yml file.

The following code shows the keys used, along with the default values:

    [php]
    ## app.yml
    all:
      nc_change_log_behavior:
        ## sfUser attribute used when obtaining the 'username' of the person performing the changes.
        username_method:              getUsername
        ## 'username' value used when running a task from cli that registers changes in the model.
        username_cli:                 cli
        ## ncChangeLogEntryFormatter child class used when formatting the text
        formatter_class:              ncChangeLogEntryFormatter
        ## if this setting is true, the plugin will fire events for field value formatting; check the *Fields formatting* section below
        formatting_events:           true
        ## Instance method used when trying to translate the class name for an object. This method may not exist.
        object_translation_method:    getHumanName
        ## Instance method used when trying to translate a field name for an object. This method may not exist.
        field_translation_method:     translateField
        ## If this is set to true, the messages will be translated using the catalogue app_name/i18n/tables/table_name. Only valid when using the default translation methods.
        translation_use_i18n:         false
        ## the date format
        date_format:                  'Y/m/d'
        ## the date time format
        date_time_format:             'Y/m/d H:i:s'
        ## the time format
        time_format:                  'H:i:s'
        ## If this is set to true, the values shown will be escaped with sfOutputEscaper::unescape. (useful when rendering values throw signals).
        escape_values:                false
        ## For foreign keys, this setting will make the plugin retrieve the foreign object and use its __toString() representation.
        get_foreign_values:           false

        ## Fields that should be ignored when looking for changes in an object...
        ignore_fields:
            ## ...for all classes; These are the actual default values, feel free to add more that apply to your needs
            any_class: [created_at, created_by, updated_at, updated_by]
            ## ...for MyOtherClass and so on... (this is just an example, not a default value)
            my_other_class: [name, height]

Custom formatter
----------------

The configured formatter class (which must be a subclass of ncChangeLogEntryFormatter) is used when showing the details of a change log entry.

You can extend this class and define your custom format strings, simply by overriding its instance protected variables. After doing so, just change the configuration value (see above) regarding the formatter class and the details will be formatted using your new class.

See the ncChangeLogEntryFormatter class for further information.


Fields formatting
-----------------
Whenever a field is rendered, a filter event is fired, with name "*TABLE_NAME*.render_*FIELD_NAME*" and the value of the field as a the parameter. This is very useful when rendering primary keys or constants. Example use:

We have a table named 'summary' that has an integer field named 'current_state'.

    
    [php]
    ## in /apps/<application>/config/<application>Configuration.class.php
    $this->dispatcher->connect('summary.render_current_state', array('Summary', 'renderCurrentState'));

Now in the "Summary" model class we associate the integer value with a string.

    [php]
    
    <?php
    // in lib/model/Summary.php
    class Summary extends BaseSummary
    {
      // ...
    
      public static function renderCurrentState($event, $value)
      {
        switch ($value)
        {
          case 1:
            $string_representation = 'New';
            break;
          case 2:
            $string_representation = 'Sold';
            break;
          default:
            $string_representation = 'Nonexistent';
        }
        
        return $string_representation;
      }
    }

If you intend to return html or javascript code, you should enable the "escape_values" option in *app.yml*.


Changelog filtering
-------------------

The behavior provides an event to filter the changelog before it is saved. 
The name of the event is "*TABLE_NAME*.nc_filter_changes". 
The subject is the Propel object handled by the behavior, and the value to be filtered is the following array:

    [php]
    array(
      'changes' => array(
        'fieldName' => array(
          'field' => # the name of the field, to be used for display
          'type'  => # the column type for the field, so that you don't have to go through the table map to get it
          'old'   => # old value of the field
          'new'   => # new value of the field
          'raw'   => array(
            'old'   => # raw old value
            'new'   => # raw new value
          )
        )
      )
    );


Foreign keys
------------

When a table has a foreign key, the plugin will show the primary key for this column. The plugin can also try to retrieve the object pointed by this column and render it by using its '__toString' method. For this, the 'get_foreign_values' has to be activated in *app.yml*.

