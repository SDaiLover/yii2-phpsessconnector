Module Usage
==============

In this guide we'll show how to use PhpSessConnector in module cases.

## Create Data Provider for Widgets, Extentions, and others.

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

## Validator Model

To configure Rules of Model, there are several of them that need to be connected to the application packages as follows:

```php
namespace app\models;

use sdailover\yii\phpsessconnector\SDActiveRecord;

class ModelClass extends SDActiveRecord
{
    //....

    public function rules()
    {
        ['attribute', SDUniqueValidator::class],
    }

    //....
}
```