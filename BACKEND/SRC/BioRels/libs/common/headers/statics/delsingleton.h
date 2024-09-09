#ifndef DELSINGLETON_H
#define DELSINGLETON_H
namespace protspace
{

class DelSingleton
{
    short mOrder;
public:
    explicit DelSingleton(const short& pOrder):mOrder(pOrder){}
    bool operator>(const DelSingleton& ds)const {return mOrder >ds.mOrder;}
};

class DelOrderSingleton
{
    DelSingleton mDS;
public:
    DelOrderSingleton(DelSingleton pDS):mDS(pDS)
    {DelManager::instance().add(this);}

    bool operator>(const DelOrderSingleton& deletor)const
    {
        return mDS > deletor.mDS;
    }
    virtual void destroy()=0;
    virtual ~DelOrderSingleton(){}
};

template<class T> class TDelOrderSing:public DelOrderSingleton
{
    T* mObj;
public:
    TDelOrderSing(T* pObj, DelSingleton ds):DelOrderSingleton(ds),mObj(pObj){}
    void destroy(){mObj->destroy_instance();}
};

/// DESTRUCTIONPhase = DelSingleton
/// DelOrderSingleton = Destructor
/// DestructionManager = DelManager
///
}
#endif // DELSINGLETON_H

