#ifndef GROUPLIST_H
#define GROUPLIST_H
#undef NDEBUG
#include <assert.h>
#include <iostream>
#include <vector>
#include <algorithm>
#include "protExcept.h"
namespace protspace
{


template<class T>
class GroupList
{
protected:
    ///
    /// \brief List of object of type T
    ///
    std::vector<T*> mList;

    ///
    /// \brief Defines whether the object is the owner of the list
    ///
    /// When true, the deletion of this object will cause the deletion
    /// of all entries listed in the list
    ///
    ///
    bool mIsOwner;

public:
    ///
    /// \brief Default constructor that reserve a list of 10 object
    ///
    GroupList();

    ///
    /// \brief Constructor with a specific number of object
    /// \param size Number of object to be reserved
    ///
    GroupList(const size_t& size,const bool& isOwner=false);


    GroupList(const GroupList& list);

    ~GroupList();

    /**
     * \brief add a new object as pointer to this grouplist
     * \param obj Object to add
     * \return Position of this object
     */
    size_t add(T* obj);

    /**
     * \brief add a new object as reference to this grouplist
     * \param obj Object to add
     * \return Position of this object
     */
    size_t add(T& obj);

    /**
     * \brief Allocate memory space needed to
     * \param obj Object to add
     * \return Position of this object
     */
    void reserve(const size_t& size);
    size_t size() const;

    /**
     * @brief get the object at the given position
     * @param pos : Position to consider
     * @throw 040101  GroupList::get Position above number of entries
     * @return Object at the given position
     */
    T& get(const size_t& pos) const;
    T& operator[](const size_t& pos){return *mList[pos];}
    bool find(const T& obj, int& pos)const;
    bool isIn(const T& obj)const;
    void clear();
    const bool& isOwner()const {return mIsOwner;}
    void setOwnership(const bool& own);
    void sort();
    void assign(const T& value);
    /**
     * @brief remove the object at the given position
     * @param pos : Position to consider
     * @throw 040201 Position above number of entries
     * Will delete the object if the GroupList owns the objects
     */
    void remove(const size_t& pos, const bool& ignoreOwnership=false);
};



template<class T>
GroupList<T>::~GroupList()
{
    clear();
}


template<class T>
void GroupList<T>::sort()
{
    std::sort(mList.begin(),mList.end());
}

template<class T>
void GroupList<T>::reserve(const size_t& size)
{
    mList.reserve(size);
}
template<class T>
GroupList<T>::GroupList()
{
    mList.reserve(10);
    mIsOwner=false;
}



template<class T>
GroupList<T>::GroupList(const size_t& size,const bool& isOwner)
{
    mList.reserve(size);
    mIsOwner=isOwner;
}



template<class T>
size_t GroupList<T>::add(T* obj)
{
    mList.push_back(obj);
    return mList.size()-1;
}

template<class T>
size_t GroupList<T>::add(T& obj)
{
    mList.push_back(&obj);
    return mList.size()-1;
}



template<class T>
size_t GroupList<T>::size() const
{
    return mList.size();
}



template<class T>
T& GroupList<T>::get(const size_t& pos) const
{
    if (pos >= mList.size())
        throw_line("040101",
                   "GroupList::get",
                   "Position "+std::to_string(pos)+
                   " above number of entries ("+std::to_string(mList.size())+")");
    return *mList.at(pos);
}



template<class T>
void GroupList<T>::remove(const size_t& pos, const bool &ignoreOwnership)
{
    if (pos >= mList.size())
        throw_line("040201",
                   "GroupList::remove",
                   "Position "+std::to_string(pos)+
                   " above number of entries ("+std::to_string(mList.size())+")");
    if (mIsOwner && !ignoreOwnership)
    {
        T* entry(mList[pos]);
        assert(entry!=nullptr);
        delete entry;
        mList[pos]=nullptr;
    }
    mList.erase(mList.begin()+pos);
}

template<class T>void
GroupList<T>::assign(const T& value)
{
    std::cout <<mList.size()<<std::endl;
    for(size_t i=0;i<mList.size();++i)
    {
        std::cout <<i<<" " <<mList.at(i)<<std::endl;
        get(i)=value;
    }
}

template<class T>void
GroupList<T>::setOwnership(const bool& own)
{
    clear();mIsOwner=own;
}



template<class T>
GroupList<T>::GroupList(const GroupList& list):mList(list.mList),mIsOwner(false){}




template<class T>
bool GroupList<T>::find(const T& obj, int& pos)const
{
    typename std::vector<T*>::const_iterator it=std::find(mList.begin(),mList.end(),&obj);
    if(it==mList.end())return false;
    pos=std::distance(mList.begin(),it);
    return true;
}



template<class T>
bool GroupList<T>::isIn(const T& obj)const
{
   const typename std::vector<T*>::const_iterator it=std::find(mList.begin(),mList.end(),&obj);
    return (it != mList.end());
}



template<class T> void GroupList<T>::clear()
{
    if (mIsOwner)
        for(size_t i=0;i<mList.size();++i)delete mList.at(i);
    mList.clear();
}

}

#endif // GROUPLIST_H

