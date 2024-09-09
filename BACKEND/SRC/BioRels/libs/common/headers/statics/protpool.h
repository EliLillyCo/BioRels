#ifndef PROTPOOL_H
#define PROTPOOL_H
#include <memory>
#include "headers/statics/objectpool.h"
#include "headers/math/coords.h"
#include "headers/math/rigidalign.h"
#include "headers/math/box.h"
namespace protspace
{

class ProtPool
{
    typedef std::unique_ptr<ProtPool> ProtPoolPtr;
    friend class std::unique_ptr<ProtPool>;
protected:
    ProtPool(const ProtPool&) {}
    ProtPool& operator=(const ProtPool&);
    ProtPool();


    static ProtPoolPtr& get_instance();

public:
    ~ProtPool();
    static ProtPool& Instance(){return *get_instance();}
    static const ProtPool& const_instance(){return Instance();}
static void destroy_instance(){delete get_instance().release();}
    static ObjectPool<Coords> coord;
    static ObjectPool<double> dbl;
    static ObjectPool<std::string> string;
    static ObjectPool<Box> box;
};

struct StringPoolObj
{
    size_t pos;
    std::string& obj;
    StringPoolObj():obj(ProtPool::Instance().string.acquireObject(pos)){obj="";}
    StringPoolObj(const std::string& p):obj(ProtPool::Instance().string.acquireObject(pos)){obj=p;}
    StringPoolObj(const StringPoolObj& pObj):
        obj(ProtPool::Instance().string.acquireObject(pos)){obj=pObj.get();}
    ~StringPoolObj(){ ProtPool::Instance().string.releaseObject(pos);}
    std::string& operator->(){return obj;}
    std::string& operator*(){return obj;}
    const std::string& operator*()const{return obj;}
    const std::string& get()const{return obj;}
    std::string& get(){return obj;}

    StringPoolObj& operator=(const std::string& pStr)
    {
        obj=pStr;
        return *this;
    }
    bool operator<(const StringPoolObj& pObj)const
    {
        return obj<pObj.obj;
    }
    bool operator==(const std::string& pObj)const{return pObj==obj;}
    bool operator==(const StringPoolObj& pObj)const{return pObj.obj==obj;}
    bool operator!=(const StringPoolObj& pObj)const{return pObj.obj!=obj;}

};

struct CoordPoolObj
{
    size_t pos;
    Coords& obj;
    CoordPoolObj(const protspace::Coords& val):obj(ProtPool::Instance().coord.acquireObject(pos)){obj=val;}
    CoordPoolObj():obj(ProtPool::Instance().coord.acquireObject(pos)){}
    ~CoordPoolObj(){ProtPool::Instance().coord.releaseObject(pos);}
    Coords* operator->(){return &obj;}
    Coords& operator*(){return obj;}
//    operator Coords(){return obj;}
    CoordPoolObj& operator=(const Coords& pStr)
    {
        obj=pStr;
        return *this;
    }

};
}
typedef protspace::StringPoolObj pS;
typedef protspace::CoordPoolObj pC;
#endif // PROTPOOL_H

