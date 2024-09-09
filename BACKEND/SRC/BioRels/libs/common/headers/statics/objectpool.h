#ifndef OBJECTPOOL_H
#define OBJECTPOOL_H

#include <algorithm>
#include <stdexcept>
#include <memory>
#include <iostream>
#undef NDEBUG
#include <assert.h>
#include "protExcept.h"
#include "grouplist.h"


namespace protspace
{
template<typename T>
class ObjectPool
{
    friend class ProtPool;
private:
    struct ObjectGroup
    {
        T* mStarter;
        size_t mCount;
        std::vector<bool> mFree;
    };
public:
    ObjectPool(const std::string name="", size_t chunkSize= kDefaultChunkSize)
    throw(std::invalid_argument,std::bad_alloc);
    ~ObjectPool();

    /**
     * @brief acquireObject
     * @param pos
     * @return
     * @throw 060101    ObjectPool::acquireObject     Given object is already in use
     */
    T& acquireObject(size_t& pos);
    void acquireList(GroupList<T>& list,std::vector<size_t>& pos, const size_t& count);

    /**
     * @brief releaseObject
     * @param pos
     * @throw 060301  ObjectPool::releaseObject Given position is above the number of objects
     * @throw 060302    ObjectPool::releaseObject     Given object  is not in use
     */
    void releaseObject(const size_t& pos);
    void releaseObject(T& obj);
    void releaseObject(const std::vector<size_t>&list);
    bool isAnyUsed()const;
    size_t countUsed()const;
    inline const size_t& getNumRequest()const {return mRequests;}
    const size_t& getMaxUsed()const{return mMaxUsed;}
    void preRequest(const size_t& pSize);
    std::string getStat()const;
protected:
    std::vector<ObjectGroup> mList;
    std::string mName;
    size_t mNextAccess;
    size_t mNextGroupAccess;
    size_t mChunkSize;
    size_t mMaxUsed;
    size_t mCurrUsed;
    size_t mRequests;
    const static  size_t kDefaultChunkSize;
    void allocateChunk(size_t size=0);
    bool getNextAccess();
    size_t getPosObject()const;


private:
    ObjectPool(const ObjectPool<T>& src);
    ObjectPool<T>& operator=(const ObjectPool<T>& rha);
}   ;

template<typename T> const size_t protspace::ObjectPool<T>:: kDefaultChunkSize=2000;

template<typename T> ObjectPool<T>::~ObjectPool()
{
    for(ObjectGroup& obj: mList)
        delete[] obj.mStarter;
}

template<typename T> ObjectPool<T>::ObjectPool(const std::string name, size_t chunkSize)
throw(std::invalid_argument,std::bad_alloc)
{
//    std::cout <<"STARTING "<<name<<std::endl;
    mName=name;
    if (chunkSize == 0)
        throw std::invalid_argument("chunk size must be positive");
    mChunkSize= chunkSize;
    mRequests=0;mMaxUsed=0;mCurrUsed=0;mNextAccess=0;
    //    allocateChunk();


    ObjectGroup objR;
    objR.mCount=chunkSize;
    for(size_t i=0;i<chunkSize;++i)objR.mFree.push_back(true);
    objR.mStarter=new T[chunkSize];
    mNextGroupAccess=0;
//    std::cout<<"START "<<mName<<" " <<mList.size()<<" " << mCurrUsed<<" " <<mMaxUsed<<std::endl;
    mNextAccess=0;
    mList.push_back(objR);
}



template<typename T>void  ObjectPool<T>::allocateChunk(size_t size)
{
    if (size==0)size =kDefaultChunkSize;
    ObjectGroup objR;
    objR.mCount=size;
    for(size_t i=0;i<size;++i)objR.mFree.push_back(true);
    objR.mStarter=new T[size];
    mNextGroupAccess=mList.size();
//    std::cout<<"REALLOCATE "<<mName<<" " <<mList.size()<<" " << mCurrUsed<<" " <<mMaxUsed<<std::endl;
    mNextAccess=0;
    mList.push_back(objR);
}

template<typename T> size_t ObjectPool<T>::getPosObject()const
{
    size_t pos=0;
    for(size_t i=0;i<mNextGroupAccess;++i)
    {
        pos+= mList.at(i).mCount;
//        std::cout <<"(POS:"<<pos<<")\t";
    }
    pos+=mNextAccess;
//    std::cout <<"(POS:"<<pos<<")\t";
    return pos;
    //    00000000001111111111
    //    01234567890123456789

    //    01234567012345601234
    //    00000000111111122222
    //    8             7  5

    //    14
}

template<typename T> T& ObjectPool<T>::acquireObject(size_t& pos)
{
    size_t currGrAcc=mNextGroupAccess;
    size_t currAcc=mNextAccess;
    pos=getPosObject();
//    std::cout <<"REQUESTING "<< mName<<" GrAcc:" <<currGrAcc<<" Acc:" <<currAcc<<"=> POS:"<<pos<<"\t"<<mCurrUsed<<"\t"<<mList.at(currGrAcc).mFree.at(currAcc)<<"\n";

//    std::cout <<"NEXT ACCESS : "<< mName<<" GrAcc:" <<currGrAcc<<" Acc:" <<currAcc<<"=> POS:"<<pos<<"\t"<<mCurrUsed;
    ObjectGroup& objG=mList.at(currGrAcc);
    //bool& currObjFree=objG.mFree.at(currAcc);
//    std::cout <<"\t"<<objG.mFree.at(currAcc)<<std::endl;
    if (!objG.mFree.at(currAcc))
    {
        std::cerr<<"Given object "<<std::to_string(currGrAcc)<<":"<<
                   std::to_string(currAcc)<<" is already in use\n";
        throw_line("060101",
                   "ObjectPool::acquireObject",
                   "Given object "+std::to_string(currGrAcc)+":"+
                   std::to_string(currAcc)+" is already in use");
    }
//    std::cout <<"GET"<<std::endl;
    T& obj = mList.at(currGrAcc).mStarter[currAcc];
    objG.mFree.at(currAcc)=false;
    mCurrUsed++;if (mCurrUsed>mMaxUsed)mMaxUsed=mCurrUsed;mRequests++;
    if (getNextAccess())
    {
//        pos=getPosObject();
        currGrAcc=mNextGroupAccess;
        currAcc=mNextAccess;
        getNextAccess();
    }
    return obj;
}


template<typename T> bool ObjectPool<T>::getNextAccess()
{

    for(size_t i=mNextGroupAccess;i<mList.size();++i)
    {
        const ObjectGroup& objG= mList.at(i);

        //std::cout <<"\tTEST "<<i<<" "<<objG.mCount<<"\n";
        for(size_t k=mNextAccess;k<objG.mCount;++k)
        {
      //      std::cout <<"\tTEST: GrAcc:"<<i<<" Acc:"<<k<<"/"<<objG.mCount<<"\t"<<objG.mFree.at(k)<<std::endl;
            if (!objG.mFree.at(k))continue;
            mNextGroupAccess=i;
            mNextAccess=k;
            return false;
        }
    }
    //std::cout <<"NO"<<std::endl;
    for(size_t i=0;i<=mNextGroupAccess;++i) //=>CORRECT
  //  for(size_t i=0;i<mNextGroupAccess;++i)
    {
        const ObjectGroup& objG= mList.at(i);
        for(size_t k = 0;k<objG.mCount;++k)
        {
//            std::cout <<"\tTEST2:"<<i<<"\t"<<k<<"/"<<objG.mCount<<"\n";
            if (!objG.mFree.at(k))continue;
            mNextGroupAccess=i;
            mNextAccess=k;
            return false;
        }
    }

    allocateChunk();
    return true;

}
template<typename T>
bool ObjectPool<T>::isAnyUsed()const
{
    for(const ObjectGroup& objG:mList)
        for(const bool& isFree:objG.mFree) if (!isFree)return true;
    return false;
}
template<typename T>
size_t ObjectPool<T>::countUsed()const
{
    size_t count=0;
    for(const ObjectGroup& objG:mList)
        for(const bool& isFree:objG.mFree) if (!isFree)++count;
    return count;
}
template<typename T> void ObjectPool<T>::releaseObject(const size_t& pos)
try{
    size_t currAcc=0,currGrAcc=0,test=0;bool found=false;
    for(;currGrAcc< mList.size();++currGrAcc)
    {
//        std::cout <<"RELEASING "<<mName<<"\t"<< test<< " < " << pos <<" < "<<test+mList.at(currGrAcc).mCount <<std::endl;
        if (pos >= test+mList.at(currGrAcc).mCount)
        {
           // std::cout <<"IN"<<std::endl;
            test+=mList.at(currGrAcc).mCount ;
            continue;
        }
        else
        {
            found=true;
            currAcc=pos-test;
//            std::cout <<"RELEASING "<<mName<<"==>"<<"GRACC:"<<currGrAcc<<"\tAcc:"<<currAcc<<std::endl;
            assert(currAcc < mList.at(currGrAcc).mCount);
            break;
        }

    }
    if (!found)
        throw_line("060301",
                   "ObjectPool::releaseObject",
                   "Given position "+mName+" "+std::to_string(pos)+" is above the "
                   +"number of objects ("+std::to_string(mList.size()));
    if(mList.at(currGrAcc).mFree.at(currAcc))
        throw_line("060302",
                   "ObjectPool::releaseObject",
                   "Given object "+mName+" "+std::to_string(pos)+" is not in use ");
//    std::cout <<"RELEASING "<<mName<<" "<<pos<<" " << currGrAcc<<" " << currAcc<<" " <<found<<" " << mList.at(currGrAcc).mFree.at(currAcc)<<" " <<countUsed()<<" " << mCurrUsed<<std::endl;
    mList.at(currGrAcc).mFree.at(currAcc)=true;
    mCurrUsed--;
}catch(ProtExcept &e)
{
    e.addHierarchy("ObjectPool<T>::releaseObject");
    throw;
}


template<typename T> void ObjectPool<T>::releaseObject(T& obj)
{
    for(ObjectGroup& objG:mList)
    {
        for(size_t i=0;i<objG.mCount;++i)
        {
            if (objG.mStarter[i]!=&obj)continue;
            objG.mFree.at(i)=true;
            mCurrUsed--;
            return ;
        }

    }
    throw_line_t("060401",
                 "ObjectPool::releaseObject",
                 "Given object is not part of this pool ",2);
}
template<typename T> void ObjectPool<T>::preRequest(const size_t& pSize)
{
    size_t countFree=0;
    for(const ObjectGroup& objG:mList)
        for(const bool& isFree:objG.mFree) if (isFree)++countFree;
    if (pSize < countFree)return;
    allocateChunk(pSize-countFree+1);
}

template<typename T> std::string ObjectPool<T>::getStat()const
{
    return mName+" "+std::to_string(countUsed())+" "+std::to_string(getMaxUsed())+" "+std::to_string(getNumRequest());

}
}
#endif // OBJECTPOOL_H

