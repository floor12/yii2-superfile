<?php

use yii\db\Schema;
use yii\db\Migration;

class m170120_210236_file extends Migration
{

    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB';

        $this->createTable(
            '{{%file}}',
            [
                'id'=> $this->primaryKey(11),
                'class'=> $this->string(255)->notNull(),
                'field'=> $this->string(255)->notNull(),
                'object_id'=> $this->integer(11)->notNull()->defaultValue(0),
                'title'=> $this->string(255)->notNull(),
                'filename'=> $this->string(255)->notNull(),
                'content_type'=> $this->string(255)->notNull(),
                'type'=> $this->integer(1)->notNull(),
                'video_status'=> $this->integer(1)->null()->defaultValue(null),
                'ordering'=> $this->integer(11)->notNull()->defaultValue(0),
                'created'=> $this->integer(11)->notNull(),
                'user_id'=> $this->integer(11)->notNull(),
                'size'=> $this->bigint(20)->notNull(),
            ],$tableOptions
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%file}}');
    } 
}
