#include "headers/statics/protpool.h"
#include "headers/statics/delmanager.h"
#include "headers/statics/delsingleton.h"
#undef NDEBUG /// Active assertion in release


protspace::ObjectPool<protspace::Coords> protspace::ProtPool::coord=protspace::ObjectPool<protspace::Coords>("Coords",3000);
protspace::ObjectPool<double> protspace::ProtPool::dbl=protspace::ObjectPool<double>("double",4000);
protspace::ObjectPool<std::string> protspace::ProtPool::string=protspace::ObjectPool<std::string>("string",50);
protspace::ObjectPool<protspace::Box> protspace::ProtPool::box=protspace::ObjectPool<protspace::Box>("Box",200);


protspace::ProtPool::ProtPoolPtr& protspace::ProtPool::get_instance()
{
    static ProtPoolPtr the_singleton(new ProtPool);
    return the_singleton;
}
protspace::ProtPool::ProtPool()
{
new protspace::TDelOrderSing<ProtPool>(this,protspace::DelSingleton(1));
}


protspace::ProtPool::~ProtPool()
{
}

