# Acf groups

## 1. What is it?

It's a composer package that provides an alternative way to working with ACF groups in code.

## 2. Why should I use this package instead of built-in ACF functions, get/set_field?

Acf groups have nice admin UI, but when you use `get/set_field` functions in your code you have some issue due to it.
The biggest are :

### a) Types issue

`get_field` function using for getting fields with all types.  
It means you always should keep in mind, what type of value (int/string/bool...) will be returned, and also remember
that `NULL` will be returned if there is no value.  
Together it's a place where making a lot of mistakes.

### b) Syncing issue

Every group change must be synced with your code.  
There is a nice built-in feature, local .json files, it uses for caching (no DB requests needed) and syncing between
different environments (like Dev/Live), but it can't solve this issue.  
E.g. when you're changing a field name in admin UI, then you've to find all places where the name is used and replace.
IDE suggestions won't work here. Usually there are a lot of groups, and some fields have similar names. Often it's very
difficult to understand is it the target name, that should be renamed or another. The same happens with all other
changes, like type change or adding a new field.

## 3. What suggests the package?

* OOP way - every group has own class, every class field corresponds to a group field.
* Fields have strict php types (int/string...) and public visibility, so no setters/getters are required (time saving
  during creating).
* All class fields will be automatically initialized with default values in a constructor (depending on a type)
* Call `load()` method with the source argument (like $postId), and values will be read from a DB and saved into the
  class fields.
* If you want to modify the values, you can just update the public fields and call a `save()` method, it'll find out
  differences with the default values and will save into a database.

The package provides two ways to use a group :

### a) Local acf groups

The local groups acf feature will be used (read about the 'acf_add_local_field_group' function).  
It means the package will get all necessary information about the group from a class, and will create a local acf
group.  
This group won't be shown in admin UI groups list, but will work like an ordinary group, so fields will be shown on
chosen locations (post/page screen...) and can be read from code.  
In this case you don't need to take care of about syncing, for example you can just add a new field into a class, and
it'll be applied for admin UI, and be available in your code, no database changes are required

### b) Db representation

If the first way doesn't work or doesn't suit you, e.g. for legacy groups, or you still want to have admin UI groups
list, then you can use group classes as a representation of DB groups,  
so it solves the types issue, and partially syncing issue, it means code changes still are required, but will be done
within one file and IDE will help in this case

## 4. How to use

### a) Install the package

`composer require lightsource/acf-groups`

### b) Require the composer autoloader

`require_once __DIR__ . '/vendors/vendor/autoload.php';`

### c) Create groups (that extend the `AcfGroup` class), create group instances using the creator and load/save values

```injectablephp
use LightSource\AcfGroups\AcfGroup;
use LightSource\AcfGroups\Creator;

class MyGroup extends AcfGroup
{
    // this constant has sense only if you use the local acf groups way, see more in the Notes
    const LOCATION_RULES = [
        [
            'post_type == page',
        ],
    ];

    // supported simple types : string, int, float, bool
    public string $firstName;
    public string $lastName;
    // clones feature, more in the Notes
    public SecondGroup $secondGroup;
    // repeater of clones feature, more in the Notes
    /**
     * @item \Namespace\SecondGroup
     */
    public array $secondGroups;
}

////////

// use one creator instance for all groups
$creator = new Creator();
$myGroup = $creator->create(MyGroup::class);

$myGroup->load(21);
echo $myGroup->firstName;

$myGroup->firstName = 'NewName';
$myGroup->save();
```

### d) Optionally. Only if you use the local acf groups way

Sign up your groups in ACF

```injectablephp
use LightSource\AcfGroups\Loader;

$loader  = new Loader();
$loader->signUpGroups('YOUR_NAMESPACE', 'YOUR_PATH');
```

## 5. Advanced

### Acf group location rules

If you use the local acf groups way then you have to override the `LOCATION_RULES` const in group classes to set up
location rules. Information about the constant value :

```injectablephp
/**
     * Should be overridden to set up location rules
     * content sample :
     * [
     *      'post_type == x',
     *      'page_template == x',
     * ],
     * [
     *      'block == x',
     * ],
     * One sub-array = one rules group. All parts within will be combined with the 'AND' rule.
     * Rule groups between self will be combined with the 'OR' rule.
     * Every string contains from 3 parts, that combined by a space : 'ParamName Operator Value'
     */
```

If you need an opportunity to set up rules dynamically, you can override the `getLocationRules()` method. For example:

```injectablephp
protected static function getLocationRules(): array
{
    $blockName = 'x';
     return [
        [
            'post_type == ' . $blockName,
        ],
    ];
}
```

### Field settings

Any acf field arguments can be set via phpDoc comments. For example

```injectablephp
/**
* @required 1
* @instructions First name. This field...
*/
public string $firstName;
```

See [acf docs](https://www.advancedcustomfields.com/resources/register-fields-via-php/) for information about available
settings. Json in argument values is supported and will be decoded - see select field sample. Also, you could
use `a-type`
alias for `type` argument (to avoid IDE warnings). You could use `a-order` argument to change fields order (useful e.g.
for extended classes)

Tab field sample :

```injectablephp
/**
* @type tab
* @placement left
*/
public bool $general;
```

Image field sample :

```injectablephp
// 'a-type' here just an alias for 'type', you can use what's preferred for you
/**
* @a-type image
* @return_format id
*/
public int $logo;
```

Select field sample:

```injectablephp
// json in the choices argument will be decoded
/**
* @type select
* @choices {"left":"Left","center": "Center","right": "Right"}
* @default_value left
*/
public string $align;
```

### Default field value

If the `get_field` function returns `NULL` then a field will be initialized with a default value for a type (0, '',
false)

### Clones

Acf clones feature is supported, so one group can contain another group in a field. By default, the clone will be
wrapped into a
tab field for comfortable usage (it can be disabled with adding '@a-no-tab 1' to phpDoc of the clone
field)

```injectablephp
class FirstGroup extends AbstractAcfGroup{
    public SecondGroup $groupField;
}
class SecondGroup extends AbstractAcfGroup{
    public string $stringField;
}
```

Behind the scene. In the related acf field settings the `clone` type will be used together with the `prefix_name`
feature, so all the `SecondGroup` fields will have a prefix from the `FirstGroup` class,  
and the end name for the `$stringField` field will
be `PREFIX_first-group__group-field_PREFIX_second-group__string-field`.

### Repeater of clones

Acf repeater feature is supported (for clones only).  
For this goal you should declare a field with the array type and it's required to fill out the php-doc `@item`
argument, that should point out to the target group class (a full class name with your namespace).  
You can also set up the php-doc `@var` argument for IDE suggestions, but it isn't required.  
(It's impossible to have a repeater of simple fields like in ACF UI in the current way, but if you're thinking about
this you'll
understand that it's almost the same, you just have to create a group for your item fields). By default, the repeater
will be
wrapped into a tab field for comfortable usage (it can be disabled with adding '@a-no-tab 1' to phpDoc of the array
field)

```injectablephp
class FirstGroup extends AbstractAcfGroup{
    /**
     * @var SecondGroup[]
     * @item \Namespace\SecondGroup
     */
    public array $groupField;
}
class SecondGroup extends AbstractAcfGroup{
    public string $stringField;
}
```

Behind the scene. In ACF a repeater field will be created with one clone field, that will point out to the target group.
The `prefix_name` feature isn't used to avoid a double prefix, so the end field name will
be : `PREFIXfirst-group__groupField_0_PREFIXsecond-group__string-field`. Also, the repeater will be wrapped into a tab
field for comfortable usage

### Acf group name

= `PREFIX.'class-name'`  
Acf group name will be based on a class name, with converting `ClassName` to `class-name`
. `AbstractAcfGroup::GROUP_NAME_PREFIX` contains a prefix and has a `group_local_` value by default, it's used to avoid
conflicts with your already existing groups. If there are reasons to change it, you can override the constant, see
the `Additional constants` information for details

### Acf field name

= `$groupName.'__'.field-name'`  
Acf field name will be based on a class name + field name, with converting `fieldName` to `field-name`. If you need to
get an acf field name (to use in a DB query for example) - you should use the `getAcfFieldName()` method

### Acf field label

= `Field Name`  
Acf field label will be based on a field name, with converting `fieldName` to `Field Name`.  
You can override it with a `@label` argument in field's phpDoc or override the `getAcfFieldLabel()` method

### Additional constants.

* `GROUP_NAME_PREFIX` by default is `group_local_`, can be changed to another. If you want to change the prefix for
  multiple
  groups, you can just create a wrapper that will override the constant and extend all groups from the wrapper
* `IS_LOCAL_GROUP` const can be overridden to `false` if you use the local acf groups way but want to exclude some
  classes from autoloading
* `CUSTOM_GROUP_NAME` const can be overridden if your group has a name that doesn't follow the above's naming agreement.
  Use it only for legacy groups supporting. In this case even the `GROUP_NAME_PREFIX` constant will be skipped

## Benchmark

On a real website. Test page used a lot of ACF Groups to get data from page's ACF Blocks (signed up using the ACF Blocks package).

Common time: 0.0218 seconds  
Load time: 0.0157 seconds per 39 groups  
Creation time: 0.00606 seconds per 193 groups

Hardware: Hetzner VPS, 4vCPU, 8GB RAM, 160GB SSD  
PHP 8.1 with opcache enabled