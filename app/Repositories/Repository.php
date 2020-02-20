<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 13.10.17
 * Time: 17:57
 */
namespace App\Repositories;
use App\Repositories\Contracts\RepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Repository implements RepositoryInterface
{
    protected $modelClassName;

    /**
     * @param array $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return call_user_func_array("{$this->modelClassName}::all", [$columns]);
    }

    /**
     * @param $perPage
     * @param array $columns
     * @return mixed
     */
    public function paginate($perPage = 10, $columns = ['*'])
    {
        return call_user_func_array("{$this->modelClassName}::paginate", [$perPage, $columns]);
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes)
    {

        return call_user_func_array("{$this->modelClassName}::create", [$attributes]);
    }


    /**
     * @param array $data
     * @param string|int $id
     * @return mixed
     */
    public function update(array $data, string $id)
    {
        /**
         * @var $model Model
         */
        $model = call_user_func_array("{$this->modelClassName}::find", [$id]);
        if(is_null($model)) {
            throw new NotFoundHttpException();
        }
        $model->fill($data)->save();
        return $model;
    }

    /**
     * @param $id
     * @return int
     */
    public function delete($id)
    {
        return call_user_func_array("{$this->modelClassName}::destroy", [$id]);
    }

    /**
     * @param $id
     * @param array $columns
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        return call_user_func_array("{$this->modelClassName}::find", [$id, $columns]);
    }

    /**
     * @param $column
     * @param $operator
     * @param $value
     * @param $boolean
     * @return Model
     */
    public function findBy($column, $operator = null, $value = null, $boolean = 'and')
    {
        $where = call_user_func_array("{$this->modelClassName}::where", [$column, $operator, $value, $boolean]);
        return $where->first();
    }

    /**
     * @param $column
     * @param $operator
     * @param $value
     * @param $boolean
     * @return Collection
     */
    public function findAllBy($column, $operator = null, $value = null, $boolean = 'and')
    {
        $where = call_user_func_array("{$this->modelClassName}::where", [$column, $operator, $value, $boolean]);
        return $where->get();
    }

    /**
     * @param bool $trashed
     * @return Collection
     */
    public function findAll(bool $trashed = false)
    {
        /**
         * @var $query Builder
         */
        $query = call_user_func_array("{$this->modelClassName}::query", []);
        if ($trashed) {
            $query->onlyTrashed();
        }
        return $query->get();
    }

    /**
     * @return Builder
     */
    public function getQuery()
    {
        return call_user_func_array("{$this->modelClassName}::query", []);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail($id, $columns = ['*'])
    {
        return call_user_func_array("{$this->modelClassName}::findOrFail", [$id, $columns]);
    }

    /**
     * Find a model by its primary key or return fresh model instance.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function findOrNew($id, $columns = ['*'])
    {
        return call_user_func_array("{$this->modelClassName}::findOrNew", [$id, $columns]);
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function firstOrFail($columns = ['*'])
    {
        return call_user_func_array("{$this->modelClassName}::firstOrFail", [$columns]);
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        return call_user_func_array("{$this->modelClassName}::firstOrCreate", [$attributes, $values]);
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function firstOrNew(array $attributes, array $values = [])
    {
        return call_user_func_array("{$this->modelClassName}::firstOrNew", [$attributes, $values]);
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        return call_user_func_array("{$this->modelClassName}::updateOrCreate", [$attributes, $values]);
    }

    public function emptyModel()
    {
        $model = new $this->modelClassName();
        return $model;
    }

    /**
     * @param $relation \Jenssegers\Mongodb\Relations\EmbedsOneOrMany
     * @param $value mixed
     * @param $key string
     * @return Model|null|static
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findEmbedById($relation, $value, $key = '_id')
    {
        /**
         * @var $relation \Jenssegers\Mongodb\Relations\EmbedsOneOrMany
         */
        if ($result = $relation->where($key, '=', $value)->first()) {
            return $result;
        } else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Not found embed relation ".class_basename($relation));
        }
    }

}