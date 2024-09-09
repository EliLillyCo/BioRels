#include <algorithm>
#include <functional>
#include "headers/statics/delmanager.h"
#include "headers/statics/delsingleton.h"


/// DESTRUCTIONPhase = DelSingleton
/// DelOrderSingleton = Destructor
/// DestructionManager = DelManager
///
///

protspace::DelManager::~DelManager()
{
    for(size_t i=0;i<mListDest.size();++i)
        delete mListDest[i];
}

protspace::DelManager::DelManagerPtr& protspace::DelManager::get_instance()
{
    static DelManagerPtr the_singleton(new DelManager);
    return the_singleton;
}
template<class T> class greater_ptr{
public:
    typedef T* T_ptr;
    bool operator()(const T_ptr& lhs, const T_ptr& rhs)const
    {return *lhs > *rhs;}
};


void protspace::DelManager::destroy_all()
{
    std::sort(mListDest.begin(),mListDest.end(),greater_ptr<DelOrderSingleton>());
    for(size_t i=0;i<mListDest.size();++i)
        mListDest[i]->destroy();

}
