# larkblog

这是一款极轻量、高效的PHP框架，上手容易、操作简单

## 框架使用说明

### Model操作

1、controller中操作model

    $xxx_model = $this->loadModel('xxx');

    $xxx_model->select();


2、model中操作db

#### 查询操作

1、获取单个结果

    $data = $this->find($id);

2、获取多个结果

    $list = $this->where($condition)->limit($limit)->order($order)->select();

3、获取结果记录总数

    $count = $this->where($condition)->count('id');

4、获取最后一条执行的sql

    $sql = $this->getLastSql();

#### 插入操作

    $insert_id = $this->add($data);

#### 更新操作

    $res = $this->where(array('id'=>$id))->save($data);

#### 删除操作

    $res = $this->where(array('id'=>$id))->delete();
