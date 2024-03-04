<p align="center">
    <a href="https://www.sdailover.com/" target="_blank">
        <img src="https://sdailover.github.io/images/logo.png" width="128px">
    </a>
    <h1 align="center">SDaiLover PHPSessionConnector for Yii 2</h1>
    <br>
</p>

# yii2-phpsessconnector

Runtime database helper to choose PHP Session or another database without change structure model of  [Yii framework 2.0](https://www.yiiframework.com).

For license information check the [LICENSE](LICENSE.md)-file.

Documentation is at [docs/guide/README.md](docs/guide/README.md).

[![PHP Language](https://img.shields.io/badge/%20Lang%20-%20PHP%208.1%20-gray.svg?colorA=2C5364&colorB=0F2027&style=flat&logo=php&logoColor=white)](https://github.com/sdailover/yii2-phpsessconnector)
[![Code Editor](https://img.shields.io/badge/%20IDE%20-%20Visual%20Code%20-gray.svg?colorA=2C5364&colorB=0F2027&style=flat&logo=visualstudio&logoColor=white)](https://github.com/sdailover/yii2-phpsessconnector)
[![PHP Framework](https://img.shields.io/badge/%20Framework%20-%20Yii%202.0%20-gray.svg?colorA=2C5364&colorB=0F2027&style=flat&logo=framework&logoColor=white)](https://github.com/sdailover/yii2-phpsessconnector)
[![CSS Bootstrap](https://img.shields.io/badge/%20CSS%20-%20Bootstrap%205.3%20-gray.svg?colorA=2C5364&colorB=0F2027&style=flat&logo=bootstrap&logoColor=white)](https://github.com/sdailover/yii2-phpsessconnector)
[![JS jQuery](https://img.shields.io/badge/%20JS%20-%20jQuery%203.2%20-gray.svg?colorA=2C5364&colorB=0F2027&style=flat&logo=jquery&logoColor=white)](https://github.com/sdailover/yii2-phpsessconnector)


[![Latest Stable Version](https://poser.pugx.org/sdailover/yii2-phpsessconnector/v/stable.png)](https://packagist.org/packages/sdailover/yii2-phpsessconnector)
[![Total Downloads](https://poser.pugx.org/sdailover/yii2-phpsessconnector/downloads.png)](https://packagist.org/packages/sdailover/yii2-phpsessconnector)
[![GitHub watchers](https://img.shields.io/github/watchers/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector)
[![GitHub Repo stars](https://img.shields.io/github/stars/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector)
[![GitHub issues](https://img.shields.io/github/forks/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector)

[![GitHub contributors](https://img.shields.io/github/contributors/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector)
[![GitHub pull requests](https://img.shields.io/github/issues-pr/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector/pulls)
[![GitHub issues](https://img.shields.io/github/issues/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector/issues)
[![GitHub Discussions](https://img.shields.io/github/discussions/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector/discussions)
[![GitHub last commit (by committer)](https://img.shields.io/github/last-commit/sdailover/yii2-phpsessconnector)](https://github.com/sdailover/yii2-phpsessconnector)


[Report Bug](https://github.com/sdailover/yii2-phpsessconnector/issues/new?assignees=&labels=bug&projects=&template=bug_report.yml)
·
[Request Feature](https://github.com/sdailover/yii2-phpsessconnector/issues/new?assignees=&labels=enhancement&projects=&template=feature_request.yml)
·
[Provide Feedback](https://github.com/sdailover/yii2-phpsessconnector/discussions/new?category=ideas&title=Suggest%20for%20SDaiLover%20Yii2%20PhpSessConnector)
·
[Ask Question](https://github.com/sdailover/yii2-phpsessconnector/discussions/new?category=q-a&title=Ask%20Question%20for%20SDaiLover%20Yii2%20PhpSessConnector)

Love the project? Please consider [donating](https://opencollective.com/sdailover) or give :star: to help it improve!

Copyright &copy; ID 2024 SDaiLover &#40;[www.sdailover.com](https://sdailover.com)&#41;

All rights reserved.

***

Installation
------------

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

App Configuration
-----

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

Model Usage
-----

To connect a database using ActiveRecord into a Model class:

```php
namespace app\models;

use sdailover\yii\phpsessconnector\SDActiveRecord;

class ModelClass extends SDActiveRecord
{
    //....

    /* Create list attribute or name's field of database. */
    public $attribute;

    /**
     * Default data imported into the php session,
     * this data only load to php session and not
     * import data to real database (mysql, sqlite, others).
     */
    private static $data = [
        [
            'attribute' => 'value',
            //....
        ]
    ];

    /**
     * Set the name of the database table or table session.
     */
    public static function tableName()
    {
        return '{{tablename}}';
    }

    /**
     * Load and import default data to php session.
     */
    public static function loadTable()
    {
        parent::records(static::$data);
    }

    //....
}
```

Provider Usage
-----

Data Providers are usually used to search in Models or display Models in the form of widgets such as GridView and other extensions. To implement it into the application created, we can configure it as follows:

```php
namespace app\models;

use app\models\ModelClass;
use sdailover\yii\phpsessconnector\SDActiveProvider;

class ModelSearchClass extends ModelClass
{
    //....

    public function search($params)
    {
        $query = ModelClass::find();

        $dataProvider = new SDActiveProvider([
            'query' => $query
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        // Add filter condition
        if ($this->attribute !== null && !empty($this->attribute))
            $query->andFilterWhere(['attribute' => $this->attribute]);
        return $dataProvider;
    }

    //....
}
```

Controller Usage
-----

To use the `SDActiveRecord` and `SDDataProvider` that have been created, we can implement them into the Controller that will be used as follows:

```php
namespace app\controllers;

use yii\web\Controller;
use app\models\ModelClass;
use app\models\ModelSearchClass;

class SiteController extends Controller
{
    //....

    /**
     * Display model from SDActiveRecord.
     */
    public function actionView()
    {
        $pkId = Yii::$app->request->isGet ? Yii::$app->request->get('attribute') : Yii::$app->request->post('attribute');
        $model = ModelClass::findOne($pkId);

        return $this->render('view', ['model'=>$model]);
    }

    /**
     * Display many model from SDActiveRecord with Data Provider.
     */
    public function actionSearch()
    {
        $searchModel = new ModelSearchClass();
        $searchParams = Yii::$app->request->isGet ? Yii::$app->request->get() : Yii::$app->request->post();
        $dataProvider = $searchModel->search($searchParams);

        return $this->render('search', ['dataProvider'=>$dataProvider]);
    }

    //....
}
```

# Support the project

We open-source almost everything We can and try to reply to everyone needing help using these projects. Obviously, this takes time. You can use this service for free.

If you are using this project and are happy with it or just want to encourage us to continue creating stuff, there are a few ways you can do it:

- Giving proper credit on the GitHub Sponsors page. [![Static Badge](https://img.shields.io/badge/%20Sponsor%20-gray.svg?colorA=EAEAEA&colorB=EAEAEA&style=fat&logo=githubsponsors&logoColor=EA4AAA)](https://github.com/sponsors/sdailover)
- Starring and sharing the project :star:
- You can make one-time donations via PayPal. I'll probably buy a coffee :coffee: or tea :tea: or cake :cake: <br>
  [![paypal.me/sdailover](https://img.shields.io/badge/%20Donate%20Now%20-gray.svg?colorA=2C5364&colorB=0F2027&style=for-the-badge&logo=paypal&logoColor=white)](https://www.paypal.me/sdailover)
- It’s also possible to support mine financially by becoming a backer or sponsor through<br>
  [![opencollective.com/sdailover](https://img.shields.io/badge/%20Donate%20Now%20-gray.svg?colorA=355C7D&colorB=2980B9&style=for-the-badge&logo=opencollective&logoColor=white)](https://www.opencollective.com/sdailover)
  
However, we also provide software development services. You can also invite us to collaborate to help your business in developing the software you need. Please contact us at:<br>
[![team@sdailover.com](https://img.shields.io/badge/%20Send%20Mail%20-gray.svg?colorA=EA4335&colorB=93291E&style=for-the-badge&logo=gmail&logoColor=white)](mailto:team@sdailover.com)

## :pray: Thanks for your contribute and support! :heart_eyes: :heart:

> Any Questions & Other Supports? see [Support](https://github.com/sdailover/.github/blob/master/SUPPORT.md) please.

***

[Visit Website](https://www.sdailover.com)
·
[Global Issues](https://github.com/sdailover/.github/issues/new/choose)
·
[Global Discussions](https://github.com/sdailover/.github/discussions)
·
[Global Wiki](https://github.com/sdailover/.github/wiki)


Copyright &copy; ID 2024 by SDaiLover &#40;[www.sdailover.com](https://sdailover.com)&#41;

[![SDaiLover License](https://upload.wikimedia.org/wikipedia/commons/thumb/1/18/Bsd-license-icon-120x42.svg/120px-Bsd-license-icon-120x42.svg.png)](https://github.com/sdailover/.github/blob/master/LICENSE.md)

All rights reserved.
