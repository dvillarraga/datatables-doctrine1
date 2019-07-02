DataTables for Doctrine 1.2
=======================

It is just a simple way to use DataTables (The JQuery Plugin) + PHP + Doctrine 1.2.4

You can use this class in order to implement a server side DataTable

For more information go to https://datatables.net/examples/data_sources/server_side.html

## Creating a simple service

```php

$q = Doctrine_Query::create()
    ->select('c.id AS c.id,
        c.fiscal_id AS c.fiscal_id,
        c.name AS c.name,
        a.name AS a.name
        ')
    ->from('Client c')
    ->leftJoin('c.Account a')
    ;

$columns = array(
    array('db' => 'c.id', 'dt' => 0),
    array('db' => 'c.fiscal_id', 'dt' => 1),
    array('db' => 'c.name', 'dt' => 2),
    array('db' => 'a.name', 'dt' => 3),
);

$data_to_render = new DataTablesDoctrine1($q, $_GET, $columns);
echo json_encode($data_to_render->getData());
exit;

```

## Sample using Symfony 1.4 (Legacy Version)

Just create a simple action in your controller...

```php

public function executeGetDataTableClients(sfWebRequest $request){
    $q = Doctrine_Query::create()
      ->select('c.id AS c.id,
          c.fiscal_id AS c.fiscal_id,
          c.name AS c.name,
          a.name AS a.name
          ')
      ->from('Client c')
      ->leftJoin('c.Account a')
      ;
    
    $columns = array(
      array('db' => 'c.id', 'dt' => 0),
      array('db' => 'c.fiscal_id', 'dt' => 1),
      array('db' => 'c.name', 'dt' => 2),
      array('db' => 'a.name', 'dt' => 3),
    );

    $data_to_render = new DataTablesDoctrine1($q, $request->getGetParameters(), $columns);
    return $this->renderText(json_encode($data_to_render->getData()));    
}

```

## Help and docs

Please feel free to contact me. You also can [tweet @dvillarraga](http://twitter.com/dvillarraga)!

## Installing DataTables Doctrine 1

The recommended way to install it is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of this lib:

```bash
php composer.phar require dvillarraga/datatables-doctrine1
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

You can then later update it using composer:

 ```bash
php composer.phar update
 ```
