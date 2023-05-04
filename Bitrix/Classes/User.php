<?php
namespace App;


class User 
{

    /**
     * Проверяет, подходит ли пароль
     * 
     * @param $userId
     * @param $password
     * @return bool
     */
    public static function isCorrectPassword(int $userId, string $password) : bool
    {
        $userData = \CUser::getByID($userId)->fetch();
        return \Bitrix\Main\Security\Password::equals($userData['PASSWORD'], $password);
    }

    /**
     * Создание пользователя по номеру телефона
     * (сработает только, если указан верный код города)
     *
     * @param string $phone номер телефона
     * @return int id пользователя
     */
    public static function createUserByPhone(string $phone) : int
    {
        $phone = normalizePhone($phone);
        if(empty($phone)) {
            return 0;
        }

        $email = 'client' . time() . rand(0, 1000) . '@mnogomeb.ru';
        $password = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(10/strlen($x)) )),1,10);
        $defaultGroups = \Bitrix\Main\Config\Option::get('main', 'new_user_registration_def_group', '');
        $fields = [
            'LOGIN' => $email,
            'EMAIL' => $email,
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
            'PERSONAL_PHONE' => $phone,
            'ACTIVE' => 'Y',
            'GROUP_ID' => $defaultGroups ? explode(',', $defaultGroups) : [2]
        ];

        $user = new \CUser();
        $userId = $user->Add($fields);
        if($userId) {
            return (int)$userId;
        }

        return 0;
    }
}