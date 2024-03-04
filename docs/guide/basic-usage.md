Basic Usage
==============

In this guide we'll show how to use PhpSessConnector in basic cases.

## Use Active Record in model class.

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

## Load model to Controller

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

    //....
}
```