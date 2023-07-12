<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    use HasFactory;

    // Таблица, из которой достаются значения
    protected $table = 'tasks'; 

    /**
     * Автоматическое управление полями created_at и updated_at
     * Во включённом состоянии нельзя самому устанавливать created_at и updated_at
     * Если выключено, то нужно вручную устанавливать значения полей created_at и updated_at, созданными методом timestamps() в миграции
     */  
    protected $timestamps = true;

    // Поля, которые можно заполнить методом экземпляра fill()
    protected $fillable = [ 
        'name',
        'description',
        'content',
        'project_id',
        'crm_id',
        'status_id',
        'creator_id',
        'previous_task_id',
        'created_at',
        'updated_at',
        'finished_at'
    ];

    // Поля, которые не будут выводиться при получении объекта модели через find() или иной способ запроса
    protected $hidden = [
        'crm_id',
        'status_id',
        'creator_id',
        'previous_task_id',
        'pivot'
    ];

    // Автоматическое приведение полей к формату
    protected $casts = [
        'created_at' => \App\Casts\DateTimeCast::class,
        'updated_at' => \App\Casts\DateTimeCast::class,
        'attachment_id' => \App\Casts\UploadedFilesCast::class
    ];

    // Автоматическая (жадная/нетерпеливая) подгрузка связей (HasOne, HasMany, BelongsTo, BelongsToMany)
    protected $with = [
        'files'
    ];

    // Здесь можно регистрировать обработчики событий и скорее всего ещё всякие штуки
    protected static function boot()
    {
        parent::boot();

        /**
         * События
         * @link https://laravel.wiki/laravel-eloquent-model-events-and-listening-methods.html
         */

        // Создание объекта модели
        // Насчёт аргумента метода не знаю
        static::retrieved(function(self $instance) {});

        // Вызывается перед созданием записи в базе данных
        static::creating(function(self $instance) {});

        // Вызывается после создания записи в базе данных
        static::created(function(self $instance) {});

        // Вызывается перед обновлением полей объекта (без сохранения в базу)
        static::updating(function(self $instance) {});

        // Вызывается после обновления полей объекта (без сохранения в базу)
        static::updated(function(self $instance) {});

        // Вызывается перед сохранением полей объекта в базу
        static::saving(function(self $instance) {});
        
        // Вызывается после сохранения полей объекта в базу
        static::saved(function(self $instance) {});

        // Вызывается перед удалением из базы
        static::deleting(function(self $instance) {});

        // Вызывается после удаления из базы
        // Насчёт аргумента метода не знаю
        static::deleted(function(self $instance) {});

        // Вызывается перед восстановлением объекта (для soft deleted записей)
        // Насчёт аргумента метода не знаю
        static::restoring(function(self $instance) {});

        // Вызывается после восстановлением объекта (для soft deleted записей)
        // Насчёт аргумента метода не знаю
        static::restored(function(self $instance) {});
    }

    /**
     * Проверка: принадлежит ли комментарий задаче
     *
     * @param int $commentId ID комментария
     *
     * @return bool
     */
    public function hasComment(int $commentId) : bool
    {
        return $this->comments()->where('id', $commentId)->select('id')->get()->isNotEmpty();
    }

    /**
     * Связь с проектом
     *
     * @return BelongsTo
     */
    public function project() : BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Связь со статусами
     *
     * @return HasOne
     */
    public function status() : HasOne
    {
        return $this->hasOne(Task\Status::class, 'id', 'status_id');
    }

    /**
     * Связь с пользователями
     *
     * @return HasOne
     */
    public function creator() : HasOne
    {
        return $this->hasOne(User::class, 'id', 'creator_id');
    }

    /**
     * Связь с предыдущей задачей
     *
     * @return HasOne
     */
    public function previousTask() : HasOne
    {
        return $this->hasOne(self::class, 'id', 'previous_task_id');
    }

    /**
     * Связь с комментариями
     *
     * @return HasMany
     */
    public function comments() : HasMany
    {
        return $this->hasMany(Task\Comment::class, 'task_id', 'id');
    }

    /**
     * Связь с файлами
     *
     * @return HasMany
     */
    public function files() : HasMany
    {
        return $this->hasMany(Task\Files::class, 'task_id', 'id');
    }

    /**
     * Связь с отметками затраченного времени
     *
     * @return HasMany
     */
    public function elapsedTime() : HasMany
    {
        return $this->hasMany(Task\ElapsedTime::class, 'task_id', 'id');
    }

    /**
     * Связь с записями истории изменений
     *
     * @return HasMany
     */
    public function history() : HasMany
    {
        return $this->hasMany(Task\History::class, 'task_id', 'id');
    }
}
