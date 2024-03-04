Installation
============

Installation consists of two parts: getting composer package and configuring an application. 

## Installing an extension

The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist sdailover/yii2-phpsessconnector
```

or add

```json
"sdailover/yii2-phpsessconnector": "~1.0.0"
```

to the `require` section of your `composer.json`.

## Configuring application

To use this extension, simply add the following code in your application configuration:

```php
return [
    //....
    'components' => [
        'db' => [
            'class' => '\sdailover\yii\phpsessconnector\SDConnection',
            'dsn' => 'phpsession:sdailover',
            // prefix name of session
            'tablePrefix' => 'sd_'
        ],
    ],
];
```