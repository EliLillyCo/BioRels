#ifndef GROUP_H
#define GROUP_H
#undef NDEBUG
#include <assert.h>
#include <iostream>
#include <vector>
#include <map>
#include <algorithm>
#include "headers/statics/protExcept.h"


template<class T, class T2, class T3> class Group
{
private:
    bool mIsNumberDotOk;

    bool mIsNumberLinksOk;


protected:
    std::vector<T*> mListDot;

    std::vector<T2*> mListLinks;

    T3& mObject;
    /**
     * @brief This group owns or not its dot or links
     *
     * When mOwner is set to true, this group is responsible for the
     * deletion of its dots and links
     *
     */
    bool mOwner;

    Group(T3* const pObject,
          const bool& pOwn,
          const size_t& nDot=50,
          const size_t& nLink=50
            )
    throw(ProtExcept):
        mIsNumberDotOk(true),
        mIsNumberLinksOk(true),
        mObject(*pObject),
        mOwner(pOwn)
    {
     try{   reserve(nDot,nLink);
        }catch(ProtExcept &e)
        {
            e.addHierarchy("Group::Group");
            throw;
        }
    }
public:
    /**
     * @brief Create of new dot for this group
     * @return Newly created dot
     * @throw 030201 - Bad allocation
     * @throw 030202   This group don't own dot, so can't create one
     */
    T& createDot()throw(ProtExcept);


    /**
     * @brief Create and return a new link that connects the two given dots
     * @param dot1 First dot to be involved
     * @param dot2 Second dot to be involved
     * @param type Type of the link
     * @return NEwly created link
     * @throw 030301 Dot 1 is not part of this Group
     * @throw 030302 Dot 2 is not part of this Group
     * @throw 030303 Group::CreateLink Bad allocation
     * @throw 030204 This group don't own link, so can't create one
     */
    T2& createLink(T& dot1,
                   T& dot2,
                   const short& type) throw(ProtExcept);

    /**
        * @brief Get the dot at the given position in the dot list of this group
        * @param pos Position in the dot list
        * @return Corresponding dot
        * @throw 030401 Given position is above the number of dots
        */
    T& getDot(const size_t& pos) const throw(ProtExcept);

    /**
        * @brief Get the link at the given position in the link list of this group
        * @param pos Position in the link list
        * @return Corresponding link
        * @throw 030901 Given position is above the number of links
        */
    T2& getLink(const size_t& pos) const throw(ProtExcept);


    /**
     * @brief Renumbers all links
     * @throw 020201 - Given link not found in this dot
     */
    void renumLink() throw(ProtExcept);




    /**
     * @brief get The position of a given dot in the dot list
     * @param dot Given dot to find position
     * @return Position of the dot
     * @throw 031001 Given dot is not part of this group
     */
    size_t getPos(T& dot)const throw(ProtExcept);


    /**
     * @brief Delete the given dot
     * @param dot
     * @throw 030701 Given dot is not part of this group
     * @throw 030702 Wrong dot MID
     * @throw 030703 dot unmatch MID
     * @throw 030801 Given Edge is not part of this graph
     * @throw 030802 Given edge is not part of this graph
     * @throw 020101 Given link is not part of this dot
     *
     *
     */
    void delDot(T& dot) throw(ProtExcept);

    /**
     * @brief delLink Delete the given link from this group
     * @param link link to delete
     * @throw 030801 Given Edge is not part of this graph
     * @throw 030802 Given edge is not part of this graph
     * @throw 020101 Given link is not part of this dot
     */
    void delLink(T2& link);

    /**
     * @brief Delete all dot and links from this group
     */
    void clear();

    /**
     * @brief Tells whether this molecule/graph own its atom,bond / vertex,edge
     * @return TRUE if it owns object, FALSE otherwise
     */
    const bool& isOwner()const {return mOwner;}
    /**
     * @brief add a Dot to this group
     * @param dot Dot to add
     * @throw 030501 This group owns link/dot. Cannot add a vertex own by another graph
     *
     * This is only valid when mOwner is set to false
     */
    void addDot(T& dot) throw(ProtExcept);

    /**
     * @brief add a Link to this group
     * @param link Link to add
     * @throw 030601 This group owns link/dot. Cannot add an link own by another group.
     * This is only valid when mOwner is set to false
     *
     */
    void addLink(T2& link) throw(ProtExcept);

    void clearLinks();

    void reserve(const size_t& nDot,
                 const size_t& nLink) throw(ProtExcept);

    ~Group();

};





template<class T, class T2, class T3>
T&  Group<T, T2, T3>::createDot()throw(ProtExcept)
{
    if (!mOwner)
        throw_line("030201",
                         "Group::CreateDot",
                         "This group don't own dot, so can't create one");
    T* dt= (T*)NULL;
    try
    {
        dt=new T(mListDot.size(),&mObject);
        mListDot.push_back(dt);
    }catch (std::bad_alloc &e)
    {
        std::string s("### BAD ALLOCATION ###\n");
        s+=e.what();s+="\n";
        throw_line("030202",
                         "Group::createDot",s);
    }
    return *mListDot.at(mListDot.size()-1);
}





template<class T, class T2, class T3>
void Group<T, T2, T3>::renumLink() throw(ProtExcept)
{
    if (mIsNumberLinksOk)return;
    int pos=-1;
    for (size_t iLink=0;iLink<mListLinks.size();++iLink)
    {
        pos++;

        if (mListLinks.at(iLink)->getMID()== pos)continue;

        T2 &bond =*mListLinks.at(iLink);

        const int former=bond.getMID();
        bond.setMID(pos);
        try{
            bond.getDot1().updateLink(former,pos);
            bond.getDot2().updateLink(former,pos);
        }catch(ProtExcept &e)
        {
            e.addHierarchy("Group::renumLink");
//            e.addDescription("DOT1:"+bond.getDot1().toString()+"\n"
//                             +"DOT2:"+bond.getDot1().toString());
            throw;
        }


    }
    mIsNumberLinksOk=true;
}





template<class T, class T2, class T3>
T2& Group<T, T2, T3>::createLink(T& dot1,
                                 T& dot2,
                                 const short& type) throw(ProtExcept)
{
    if (!mOwner)
        throw_line("030304",
                         "Group::CreateLink",
                         "This group don't own link, so can't create one");
    if (dot1.mParent != this)
        throw_line("030301",
                         "Group::createLink",
                         "Dot 1 is not part of this group");
    if (dot2.mParent != this)
        throw_line("030302",
                         "Group::createLink",
                         "Dot 2 is not part of this group");

    T2 *edge=(T2 *)NULL;
    try{
        if (!mIsNumberLinksOk) renumLink();
        edge = new T2(mObject,dot1,dot2,mListLinks.size(),type);
        mListLinks.push_back(edge);
    }catch (std::bad_alloc &e )
    {
        std::string s("### BAD ALLOCATION ###\n");
        s+=e.what();s+="\n";
        throw_line("030303",
                         "Group::createLink",
                         "Bad allocation");
    }
    catch(ProtExcept &e)
    {
        e.addHierarchy("Group::createLink");
        throw;
    }
    return *edge;
}





template<class T, class T2, class T3>
T& Group<T, T2, T3>::getDot(const size_t& pos) const throw(ProtExcept)
{
    if (pos >= mListDot.size())
        throw_line("030401",
                         "Group::getDot",
                         "Given position is above the number of dot in the group ("
                   +std::to_string(pos)+"/"+std::to_string(mListDot.size())+")");
    return *mListDot.at(pos);

}





template<class T, class T2, class T3>
T2& Group<T, T2, T3>::getLink(const size_t& pos) const throw(ProtExcept)
{
    if (pos >= mListLinks.size())
        throw_line("030901",
                         "Group::getLink",
                         "Given position is above the number of links in the group"
                   +std::to_string(pos)+"/"+std::to_string(mListLinks.size())+")");
    return *mListLinks.at(pos);
}













template<class T, class T2, class T3>
size_t Group<T, T2, T3>::getPos(T& dot)const throw(ProtExcept)
{

    for (size_t ib=0;ib < mListDot.size();++ib)
    {
        if (mListDot.at(ib) != &dot)continue;
        return ib;
    }
    throw_line("031001",
                     "Group::GetPos",
                     "Given dot is not part of this group");
}






template<class T, class T2, class T3>
void Group<T, T2, T3>::delDot(T& dot)  throw(ProtExcept)
{
//    std::cout<<dot.toString()<<std::endl;
    if (dot.mParent != this)
        throw_line("030701",
                         "Group::delDot",
                         "Given dot is not part of this group");
    if (dot.getMID() >= (int)mListDot.size())
        throw_line("030702",
                         "Group::delDot",
                         "Wrong dot MID");


    if ( mListDot.at(dot.getMID()) != &dot)
        throw_line("030703",
                         "Group::delDot",
                         "dot unmatch MID");



    const size_t nBonds =dot.numDot();
    const size_t dotMID =dot.getMID();

    std::vector<int>& listLink=dot.mListLinks;
    std::sort(listLink.begin(),listLink.end());
    for (size_t iBond=0;iBond < nBonds;++iBond)
    {
        T2& bond = getLink(listLink.at(0));

        try
        {
            delLink(bond);
        }catch (ProtExcept &e)
        {
            e.addHierarchy("Group::delDot");
            e.addDescription("Issue while deleting bond");
            throw;
        }
    }

    assert(&dot == mListDot[dotMID]);
    assert(&dot == *(mListDot.begin()+dotMID));

    delete mListDot[dotMID];
    mListDot.erase(mListDot.begin()+dotMID);

    for(size_t i=dotMID;i<mListDot.size();++i)
    {
        T& dot2=*mListDot.at(i);
//std::cout << i<<" " << mListDot.size()<<"\t"<<dot2.numDot()<<std::endl;
        for (size_t iLi=0;iLi<dot2.numDot();++iLi)
        {
          //  std::cout <<"\t"<< iLi<<std::ends;
            T2& link=*mListLinks.at(dot2.getLink(iLi));
            T& dotLink=link.getOther(dot2);
           // std::cout << "\tL"<<std::ends;
            dotLink.updateDot(dot2.getMID(),i);
           // std::cout << "\tV"<<std::ends;
        }

        dot2.setMID(i);
    }
//std::cout<<"E"<<std::endl;
}




template<class T, class T2, class T3>
void Group<T, T2, T3>::delLink(T2& link)
{
    // Check that this bond is indeed in the molecule

    if (&link.getParent() != this)
        throw_line("030801",
                         "Group::delLink",
                         "Given link is not part of this group");
    // Find the position of the bond in the list of bond of the molecule
    typedef typename std::vector<T2*>::iterator itr;
    itr itPos(find(mListLinks.begin(),
                   mListLinks.end(),
                   &link));

    if (itPos == mListLinks.end())
        throw_line("030802",
                         "Group::delLink",
                         "Given link is not part of this group");

    //Each bond has an associated MID. The latter defines the position
    // of the bond in the list of bond in the molecule (1, 2, 3, 4, 5)
    // When we delete a bond (let's say bond 3), the MID of bond 4 and 5
    // becomes wrong since their position are 3 and 4. So we need to update
    // all bonds above the bond that we delete.

    /**
     * @brief For Exception purpose, add description of the bond to the exception output
     */
    bool bond_del=false;


    /**
     * @brief MID of the bond we are deleting. Used as a starter for renumbering
     */
    const int bond_mid = link.getMID();

    /**
     * @brief First dot involved in the bond
     */
    T& dot1=link.getDot1();

    /**
     * @brief Second dot involved in the bond
     */
    T& dot2=link.getDot2();


    try{

        // Delete the link from the dot 1
        dot1.delLink(bond_mid);

        // Delete the link from the dot 2
        dot2.delLink(bond_mid);


        // Delete the bond in the bond list. This does not delete the bond itself
        mListLinks.erase(itPos);


        // Delete the bond
        delete &link;
        bond_del=true;

        // Scanning all bond with MID above the deleted one
        /// To update their MID
        for (size_t iBond=bond_mid;iBond < mListLinks.size();++iBond)
        {
            T2& bond= *mListLinks.at(iBond);
            //Since atom does not have reference nor pointer to the bond
            // but the associated MID, we need to update them as well
            // by giving the former MID and the new MID

            bond.getDot1().updateLink(bond.getMID(),iBond);
            bond.getDot2().updateLink(bond.getMID(),iBond);
            bond.setMID(iBond);
        }

    }catch (ProtExcept &e)
    {
        e.addHierarchy("Group::delLink");
        if (bond_del) e.addDescription("Deletion of link complete\n");
        //else e.addDescription("Deleting link :"+link+"\n");

        throw;
    }

}






template<class T, class T2, class T3>
void Group<T, T2, T3>::clear()
{
    for (size_t i=0;i<mListDot.size();++i) delete mListDot[i];
    for (size_t i=0;i<mListLinks.size();++i) delete mListLinks[i];
    mListDot.clear();
    mListLinks.clear();
}





template<class T, class T2, class T3>
Group<T, T2, T3>::~Group()
{
    if (!mOwner)return;
    for (size_t i=0;i<mListDot.size();++i) delete mListDot[i];
    for (size_t i=0;i<mListLinks.size();++i) delete mListLinks[i];
}






template<class T, class T2, class T3>
void Group<T, T2, T3>::addDot(T& vertex) throw(ProtExcept)
{
    if (mOwner)
        throw_line("030501",
                         "Group::addDot",
                         "This group owns link/dot. Cannot add a vertex own by another graph");
    mListDot.push_back(&vertex);
}





template<class T, class T2, class T3>
void Group<T, T2, T3>::addLink(T2& edge) throw(ProtExcept)
{
    if (mOwner)
        throw_line("030601",
                         "Group::addLink",
                         "This group owns link/dot. Cannot add an link own by another group.");
    mListLinks.push_back(&edge);
}


template<class T, class T2, class T3>
void Group<T, T2, T3>::clearLinks()
{

    for (size_t i=0;i<mListLinks.size();++i) delete mListLinks[i];

    mListLinks.clear();
}

template<class T, class T2, class T3>
void Group<T, T2, T3>::reserve(const size_t& nDot, const size_t& nLink) throw(ProtExcept)
{
    try
    {
        mListDot.reserve(nDot);
        mListLinks.reserve(nLink);
    }
    catch(std::bad_alloc &e)
    {
        std::string s("### BAD ALLOCATION ###\n");
        s+=e.what();s+="\n";
        throw_line("030101",
                         "Group::reserve",s);
    }
}

#endif // GROUP_H
