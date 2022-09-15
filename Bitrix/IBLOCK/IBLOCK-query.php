<?php
$request = CIBlockElement::getList([
            "filter" => [
                'IBLOCK_ID' => 5, // выборка элементов из инфоблока с ИД равным «5»
                'ACTIVE' => 'Y',  // выборка только активных элементов
            ],
            "select" => [
                "ID", "TITLE", "STAGE_ID", "RESPONSIBLE_ID", "CREATED_DATE", "GROUP_ID",
                "ZOMBIE", // Если удалена, то будет просто помечена ZOMBIE = true
                "OWNER_ID_LIST" => "UF_CRM_TASK",
                "ELAPSED_TIME_ID" => "ELAPSED_TIME.ID",
                "SECONDS" => "ELAPSED_TIME.SECONDS",
                "COMMENT" => "ELAPSED_TIME.COMMENT_TEXT",
                "COMMENT_DATE" => "ELAPSED_TIME.CREATED_DATE",
                "USER_ID" => "USER_INFO.ID",
                "USER_FIRSTNAME" => "USER_INFO.NAME",
                "USER_LASTNAME" => "USER_INFO.LAST_NAME",
                "GROUP_OWNER_ID" => "GROUP_INFO.OWNER_ID",
                "GROUP_OWNER_NAME" => "GROUP_INFO.NAME",
            ],
            "runtime" => [
                new Reference(
                    "ELAPSED_TIME",
                    ElapsedTimeTable::class,
                    Join::on('this.ID', 'ref.TASK_ID'),
                ),
                new Reference(
                    "USER_INFO",
                    UserTable::class,
                    Join::on("this.ELAPSED_TIME.USER_ID", "ref.ID")
                ),
                new Reference(
                    "GROUP_INFO",
                    WorkgroupTable::class,
                    Join::on("this.GROUP_ID", "ref.ID")
                )
            ]
        ]);