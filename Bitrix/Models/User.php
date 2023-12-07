<?php

namespace App\Models;

class User extends Model
{
    public static string $table = \Bitrix\Main\UserTable::class;

    protected static string $ufTable = 'b_uts_user';

    private static self $currentUser;
    private bool $isAdmin;
    private array $userGroupList;

    /**
     * Получение текущего пользователя
     *
     * @return self
     */
    public static function getCurrent(): self
    {
        if (isset(self::$currentUser)) {
            return self::$currentUser;
        }

        global $USER;
        $userId = (int)$USER->getId();
        if (empty($userId)) {
            return new self(0, []);
        }

        return self::$currentUser = self::find($userId);
    }

    /**
     * Установка текущего пользователя
     *
     * @param int $userId
     *
     * @return self
     */
    public static function setCurrent(int $userId): User
    {
        global $USER;
        if ($userId === 0) {
            $USER->logout();
            return self::$currentUser = new self(0, []);
        }

        $user = self::find($userId);
        if ($user === null) {
            return new self(0, []);
        }

        $USER->authorize($userId);
        return self::$currentUser = $user;
    }

    /**
     * Получение ФИО пользователя
     *
     * @return string
     */
    public function getFullName(): string
    {
        $nameList = array_filter([$this->getField('LAST_NAME'), $this->getField('NAME'), $this->getField('SECOND_NAME')]);
        return implode(' ', $nameList);
    }

    /**
     * Получение электронной почты
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->getField('EMAIL');
    }

    /**
     * Проверка: является ли пользователь администратором
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        if (isset($this->isAdmin)) {
            return $this->isAdmin;
        }

        return $this->isAdmin = in_array(1, $this->getGroupList());
    }

    /**
     * Проверка: авторизован ли пользователь
     *
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->getId() !== 0;
    }

    /**
     * Получение id групп, к которым принадлежит пользователь
     *
     * @return array
     */
    public function getGroupList(): array
    {
        if (isset($this->userGroupList)) {
            return $this->userGroupList;
        }

        return $this->userGroupList = \CUser::GetUserGroup($this->getId()) ?: [];
    }
}