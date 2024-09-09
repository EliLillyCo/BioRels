#ifndef DOT_H
#define DOT_H
#include <map>
#include <vector>
#include <algorithm>
#include "headers/statics/protExcept.h"
#include "headers/statics/ids.h"

namespace protspace
{






template<class T, class T3> class dot:public ids
{

protected:

    /**
     * @brief mParent Parent object of this dot
     */
    T* mParent;

    /**
     * @brief List of links this dot in involved with
     */
    std::vector<int> mListLinks;






public:
    ~dot();
    /**
     * @brief Standard constructor
     * @param Parent of this dot
     * @param MId Id as given by the parent object
     * @param FId Id as given by the input file
     * @throw 020601 Bad allocation
     */
    dot(T* const Parent, const int& MId, const int& FId);

    /**
     * @brief add a new link to this dot
     * @param dotValue IId of the Other dot involved in this link
     * @param linkValue IId of the linker that exists between this dot and the given dot
     */
    void add(const int& dotValue, const int& linkValue);

    /**
     * @brief Get the dot at the given position in the list of linked dots
     * @param pos Position of the dot
     * @return The corresponding linked dot
     * @throw 020401 Position is above the number of linked dots
     * @test testVertex
     * Position range from 0 to numDot
     */
    const int& getDot(const size_t& pos)const  throw(ProtExcept)
    {
        if (pos>mListDots.size())
            throw_line("020401",
                       "dot::getDot",
                       "Position is above the number of linked dots");
        return mListDots.at(pos);
    }


    /**
     * @brief Get the link at the given position in the list of links
     * @param pos Position of the link
     * @return The corresponding link
     * @throw 020501 Position is above the number of links
     * @test testVertex
     */
    const int& getLink(const size_t& pos)const
    {
        if (pos>mListLinks.size())
            throw_line("020501",
                             "dot::getLink",
                             "Position is above the number of links");
        return mListLinks.at(pos);
    }

    /**
     * @brief Number of dots/links the dot have
     * @return Number of dots
     * @test testVertex
     */
    size_t numDot() const {return mListDots.size();}

    /**
     * @brief Returns a iterator of the position of the given
     * @param obj Object to find
     * @return Iterator of this object or std::vector<const T3*>::end() if not found
     */
    const typename std::vector<const T3*>::iterator find(const T3* obj);

    /**
     * @brief Delete the link that has the position param in the parent object
     * @param param Position in the position object of the link
     * @throw 020101 Given link is not part of this dot
     */
    void delLink(const int& param) throw(ProtExcept);

    /**
     * @brief Update the position of the link from former to current
     * @param former Former position of the link
     * @param current New position
     * @throw 020201 Given link not found in this dot
     *
     * In the case of the deletion of a dot from the parent vector,
     * all values of other dots are invalidated. This function is here to
     * update their values
     */
    void updateLink(const int& former, const int& current);

    /**
     * @brief Update the position of the dot from former to current
     * @param former Former position of the dot
     * @param current New position of the dot
     * @throw 020301 Given dot not found in this dot
     */
    void updateDot(const int& former, const int&current);


    T& getParent() const{return *mParent;}
    const T& getParentMole()const{return *mParent;}

/**
 * @brief List of dots linked by this dot through links
 */
std::vector<int> mListDots;
};

}
template<class T,  class T3>
protspace::dot<T,T3>::~dot()
{

}




template<class T, class T3>  protspace::dot<T,T3>::
dot(T* const Parent, const int& MId, const int& FId):
    ids(MId,FId),
    mParent(Parent),
    mListLinks(0),
    mListDots(0)
{
    try
    {
    // Most atoms makes up to 4 bonds.
mListDots.reserve(4);
mListLinks.reserve(4);
    }catch(std::bad_alloc &e)
    {
        std::string s("### BAD ALLOCATION ###\n");
        s+=e.what();s+="\n";
        throw_line("020601",
                   "Dot::Dot",
                   s);

    }
}


template<class T,  class T3> void
protspace::dot<T,T3>::add(const int& dotValue, const int& linkValue)
{

    mListDots.push_back(dotValue);
    mListLinks.push_back(linkValue);
}


template<class T, class T3>
const typename  std::vector<const T3*>::iterator
protspace::dot<T,T3>::find(const T3* param)
{
    return std::find(mListLinks.begin(),mListLinks.end(),param);
}



template<class T,  class T3>
void protspace::dot<T,T3>::delLink(const int& param)throw(ProtExcept)
{
    size_t pos=0;
    // Searching the link with the given param:
    for (; pos<= mListLinks.size();++pos)
    {
        if (pos == mListLinks.size())
            throw_line("020101",
                             "dot::delLink",
                             "Given link is not part of this dot");
        if (mListLinks.at(pos)== param)
            break;
    }

    mListLinks.erase(mListLinks.begin()+pos);
    mListDots.erase(mListDots.begin()+pos);
}




template<class T,  class T3>
void protspace::dot<T,T3>::updateLink(const int& former, const int& current)
{

    for (size_t pos=0; pos<= mListLinks.size();++pos)
    {
        if (pos == mListLinks.size())
            throw_line("020201",
                             "dot::updateLink",
                             "Given link not found in this dot");
        if (mListLinks.at(pos)!= former)continue;
        mListLinks.at(pos)=current;return;

    }
}



template<class T,  class T3>
void protspace::dot<T,T3>::updateDot(const int& former, const int& current)
{

    const std::vector<int>::iterator it
            =std::find(mListDots.begin(),
                       mListDots.end(),
                       former);


    if (it == mListDots.end())
        throw_line("020301",
                         "dot::updateDot",
                         "Given dot not found in this dot");
    (*it)=current;
}





#endif // DOT_H
