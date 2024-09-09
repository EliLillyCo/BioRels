#ifndef DELMANAGER_H
#define DELMANAGER_H

#include <memory>
#include <vector>


namespace protspace
{
class DelOrderSingleton;
class DelManager
{
    typedef std::unique_ptr<DelManager> DelManagerPtr;
    std::vector<DelOrderSingleton*> mListDest;
    static DelManagerPtr& get_instance();
    DelManager(){}
    friend class std::unique_ptr<DelManager>;
    DelManager(const DelManager&);
    DelManager& operator=(const DelManager&);
public:
    ~DelManager();
    static DelManager& instance(){return *get_instance();}
    static const DelManager& const_instance(){return instance();}
    void add(DelOrderSingleton* dos){mListDest.push_back(dos);}
    void destroy_all();
};
}
#endif // DELMANAGER_H


