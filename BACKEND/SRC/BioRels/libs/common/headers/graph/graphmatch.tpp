#include <algorithm>
#include <map>
#include <iostream>
#ifdef WINDOWS
#endif

//#define GRAPH_MATCH_DEBUG 1
#ifdef LINUX
#include <pthread.h>
#ifdef GRAPH_MATCH_DEBUG
#endif
#endif
#include <sstream>

//#define NUM_THREADS 5


#include "graphmatch.h"


template<class T>
 size_t protspace::GraphMatch<T>::mMaxCliqueAllowed=100000;
template<class T>
protspace::ObjectPool<std::vector<protspace::Vertex*>> protspace::GraphMatch<T>::mPoolVe("GMATCH");

template<class T> protspace::GraphMatch<T>::GraphMatch():
    mPairList(1000,true),
    cliquelist(100,true)
{
    mListAllClique=false;
    mMinSizeClique=3;
mStrictDistinct=false;
    mFullScan=true;
    mIsVirtual=true;
}






template<class T>
protspace::Pair<T>&
protspace::GraphMatch<T>::addPair(T& obj1, T& obj2)
throw(ProtExcept)
try
{
    Pair<T> *paire=new Pair<T>(obj1,obj2,mProdGraph.addVertex());
    const size_t pos(mPairList.add(paire));

    return mPairList.get(pos);
}catch(ProtExcept &e)
{
    /// PairList.get should be always working
    assert(e.getId()!="040101");
    /// Product graph must own object, otherwise wrong design
    assert(e.getId() !="030202");



    e.addHierarchy("GraphMatch::addPair");
    //e.addHierarchy(std::string("Object 1 : ")+obj1);
    //e.addHierarchy(std::string("Object 2 : ")+obj2);
    throw;
}catch(std::bad_alloc &e)
{
    std::string s("### BAD ALLOCATION ###\n");
    s+=e.what();s+="\n";
    throw_line("110101",
               "GraphMatch::addPair",s);
}





template<class T> void
protspace::GraphMatch<T>::addLink(PairT &pair, PairT &pair2)throw(ProtExcept)
try
{
    mProdGraph.addEdge(pair.vertex,pair2.vertex);
}catch(ProtExcept &e)
{
    /// pair or pair2 is not part of this graphMatch
    if (e.getId()=="030301" || e.getId()=="030302")
    {
        e.addHierarchy("GraphMatch::addLink");
//        e.addHierarchy("Object 1 : \n"
//                       +pair.obj1+"\n"
//                       +pair.obj2);
//        e.addHierarchy("Object 2 : "
//                       +pair2.obj1+"\n"
//                       +pair2.obj2);
        throw;
    }
    else if (e.getId()=="030303")
    {
        e.addHierarchy("GraphMatch::addLink");
        throw;
    }
    /// ProdGraph is not owner - Shouldn't happen
    assert(e.getId()!="030204");
    /// Unexpected exception
    assert(1==0);
}



template<class T> void
protspace::GraphMatch<T>::addLink(const size_t& posR, const size_t& posC)throw(ProtExcept)
try
{
    mProdGraph.addEdge(posR,posC);
}catch(ProtExcept &e)
{

    /// ProdGraph is not owner - Shouldn't happen
    assert(e.getId()!="030204");

        e.addHierarchy("GraphMatch::addLink");
        throw;
}




template<class T> protspace::Edge&
protspace::GraphMatch<T>::addLinkwEdge(Pair<T> &pair, Pair<T> &pair2) throw(ProtExcept)
try
{
    return mProdGraph.addEdge(pair.vertex,pair2.vertex);
}catch(ProtExcept &e)
{
    /// pair or pair2 is not part of this graphMatch
    if (e.getId()=="030301" || e.getId()=="030302")
    {
        e.addHierarchy("GraphMatch::addLink");
        e.addHierarchy("Object 1 : \n"
                       +pair.obj1+"\n"
                       +pair.obj2);
        e.addHierarchy("Object 2 : "
                       +pair2.obj1+"\n"
                       +pair2.obj2);
        throw;
    }
    else if (e.getId()=="030303")
    {
        e.addHierarchy("GraphMatch::addLink");
        throw;
    }
    /// ProdGraph is not owner - Shouldn't happen
    assert(e.getId()!="030204");
    /// Unexpected exception
    assert(1==0);
}






template<class T>
protspace::GraphMatch<T>::~GraphMatch()
{
}





template<class T> void protspace::GraphMatch<T>::clear()
{
    mPairList.clear();
    cliquelist.clear();
    mOrder.clear();
    mTmpresultlist.clear();
    mPairList.clear();
    mProdGraph.clear();

}


template<class T> void protspace::GraphMatch<T>::clearEdges()
{
    cliquelist.clear();
    mOrder.clear();
    mTmpresultlist.clear();
    mProdGraph.clearLinks();

}



template<class T> void protspace::GraphMatch<T>::getOrder()
try{
    std::multimap<size_t,const Vertex*> vertex_degree;

    for (size_t i=0;i<mProdGraph.numVertex();++i)
    {
        const Vertex& vertex = mProdGraph.getVertex(i);
        vertex_degree.insert(std::make_pair(vertex.numDot(),&vertex));
    }

    for ( std::multimap<size_t,const Vertex*>::reverse_iterator
          it= vertex_degree.rbegin();
          it!=vertex_degree.rend();
          ++it)
    {
        if ((*it).first <mMinSizeClique-1)continue;
        mOrder.push_back((*it).second->getMID());
    }
    mNPair=mOrder.size();

}catch(ProtExcept &e)
{
    /// Vertex position shouldn't be above the number of vertices
    assert(e.getId()!= "030401");
    e.addHierarchy("GraphMatch::getOrder");
    throw;
}





template<class T> size_t
protspace::GraphMatch<T>::getCandidates(
        const size_t& curr_i,
        const Vertex& curr_look,
        const size_t& level)const
{

    size_t nCandidates=1;

    for (size_t j=0; j<mNPair;++j)
    {
        const Vertex& veJ=mPairList.get(mOrder.at(j)).vertex;
        //j>curr_i &&
        if (curr_look.hasEdgeWith(veJ))
        {
            //mLevelCand.setVal(level,j,true);
            /// JD ADD 24 Oct 2017
            /// A pair cannot be a candidate if it wasn't on the previous level
            if (level==0)mLevelCand.setVal(level,j,true);
            else if (mLevelCand.getVal(level-1,j))mLevelCand.setVal(level,j,true);
            else mLevelCand.setVal(level,j,false);
            nCandidates++;
#ifdef GRAPH_MATCH_DEBUG
            std::cout << veJ.getMID()<<" ";
#endif
        }
        else
        {
            mLevelCand.setVal(level,j,false);
#ifdef GRAPH_MATCH_DEBUG
            std::cout << "/"<<veJ.getMID()<<" ";
#endif
        }
    }
#ifdef GRAPH_MATCH_DEBUG
    std::cout <<std::endl;
#endif
    return nCandidates;
}


template<class T> size_t
protspace::GraphMatch<T>::getUniqRefCand(
        const size_t& level)
{
    assert(mOrder.size()==mNPair);
//    size_t pos;
    ///PREV: veList& list=mPoolVe.acquireObject(pos);list.clear();;
    ///UPDATE 010417 JD
    std::vector<T*>& list = t_refCandlist;list.clear();

    for (size_t j=0; j<mNPair;++j)
    {
        if (!mLevelCand.getVal(level,j))continue;
        list.push_back(&mPairList.get(mOrder.at(j)).obj1);
    }
    std::sort(list.begin(),list.end());
    const size_t dist = std::distance(list.begin(),
                         std::unique(list.begin(),list.end()));
//    mPoolVe.releaseObject(pos);;
    return dist;
}



template<class T>
bool protspace::GraphMatch<T>::checkOverlap(const std::vector<Vertex*>& vl,
                                            const Clique<T>& cl)const
{
    size_t count=0;
    bool found=false;
    for(size_t iVl=0;iVl< vl.size();++iVl)
    {
        const Vertex& ve=*vl.at(iVl);found=false;
        for(size_t iCl=0;iCl<cl.listpair.size();++iCl)
        {
            const Vertex& pair=cl.listpair.at(iCl)->vertex;

            if (ve.getMID()!=pair.getMID())continue;
            found=true;++count;
            break;
        }

        if (!found){//std::cout<<"NOT FOUND"<<std::endl;
            return false;}
}
//    std::cout <<"TEST:"<<count<<"/"<<vl.size()<<"/"<<cl.listpair.size()<<std::endl;
    return true;
}


template<class T>
bool protspace::GraphMatch<T>::checkOverlap(const std::vector<Vertex*>& vl,
                                            const std::vector<Vertex*>& cl)const
{
    size_t count=0;
    bool found=false;
    for(size_t iVl=0;iVl< vl.size();++iVl)
    {
        const Vertex& ve=*vl.at(iVl);found=false;
        for(size_t iCl=0;iCl<cl.size();++iCl)
        {
            const Vertex& veCl=*cl.at(iCl);

            if (ve.getMID()!=veCl.getMID())continue;
            found=true;++count;
            break;
        }

        if (!found){//std::cout<<"NOT FOUND"<<std::endl;
            return false;}
}
//    std::cout <<"TEST:"<<count<<"/"<<vl.size()<<"/"<<cl.listpair.size()<<std::endl;
    return true;
}




template<class T>
void protspace::GraphMatch<T>::convertToAllClique()
{
    std::vector<std::vector<Vertex*> > full_clique_list;
    for (size_t iCl=0;iCl < mTmpresultlist.size();++iCl)
    {
        std::vector<Vertex*>& vl=mTmpresultlist.at((iCl));
        std::vector<Vertex*> velist;
        getNext(vl,velist,full_clique_list,0);
    }

    std::sort(full_clique_list.begin(),full_clique_list.end());
        for(const veList& velist:full_clique_list)
        {
        if (velist.size() < mMinSizeClique) continue;
        Clique<T> *newClique= new Clique<T>(velist.size());
        for (const Vertex* ve:velist)
        {
            newClique->listpair.push_back(&mPairList.get(ve->getMID()));
        }
        cliquelist.add(newClique);
    }
}



template<class T>
void protspace::GraphMatch<T>::filterCliques()
{
    std::multimap<size_t,size_t> list;
    for (size_t iCl=0;iCl < mTmpresultlist.size();++iCl)
    {
        const std::vector<Vertex*>& vl=mTmpresultlist.at((iCl));
        list.insert(std::make_pair(vl.size(),iCl));
    }
std::vector<veList> newList;
    for(auto it=list.rbegin();it != list.rend();++it)
    {
//        std::cout <<"CLIQUE SIZE:"<<(*it).first<<std::endl;
        const veList& vl=mTmpresultlist.at((*it).second);
        bool fail=false;
        for(size_t iCli=0;iCli < newList.size();++iCli)
        {
            const veList& t=newList[iCli];
            /// checkOverlap requires that two cliques has mimimun 1 pair different
            if (checkOverlap(vl,t)) {fail=true;break;}
        }
        if (fail)continue;
        newList.push_back(vl);
    }
    //std::cout <<"SHRINKING FROM "<< mTmpresultlist.size()<<" TO "<<newList.size()<<std::endl;
    mTmpresultlist=newList;

}
template<class T>
void protspace::GraphMatch<T>::convertToClique()
{
    if (mListAllClique){   convertToAllClique();return;}
//std::cout <<mTmpresultlist.size()<<std::endl;
    std::multimap<size_t,size_t> list;
    for (size_t iCl=0;iCl < mTmpresultlist.size();++iCl)
    {
        const std::vector<Vertex*>& vl=mTmpresultlist.at((iCl));
        list.insert(std::make_pair(vl.size(),iCl));
    }

    for(auto it=list.rbegin();it != list.rend();++it)
    {
        const veList& vl=mTmpresultlist.at((*it).second);
        bool fail=false;
        for(size_t iCli=0;iCli < cliquelist.size();++iCli)
        {
            const Clique<T>& t=cliquelist.get(iCli);
            /// checkOverlap requires that two cliques has mimimun 1 pair different
            if (checkOverlap(vl,t)) {fail=true;break;}
        }
        if (fail)continue;
        Clique<T> *newClique= new Clique<T>(vl.size());
            for(const Vertex* ve:vl)
        {
           const int&veID =ve->getMID();
            newClique->listpair.push_back(&mPairList.get(veID));
        }
        cliquelist.add(newClique);
    }

}





template<class T>
void protspace::GraphMatch<T>::getNext(const std::vector<Vertex*>& full_list,
                                       std::vector<Vertex*> currSel,
                                       std::vector<std::vector<Vertex*> >& endlist,
                                       const size_t& last)
{
    if (last == full_list.size())
    {
        endlist.push_back(currSel);
    }
    else
    {
        getNext(full_list,currSel,endlist,last+1);
        currSel.push_back(full_list.at(last));
        // endlist.push_back(currSel);
        getNext(full_list,currSel,endlist,last+1);
    }

}





template<class T> bool
protspace::GraphMatch<T>::checkSize(const std::vector<bool>& candidates,
                                    size_t currSize)const
{

    // NEW ADDITION

    for (size_t pos=0; pos < mOrder.size();++pos)
    {
        if (!candidates.at(pos)) continue;
        currSize++;
    }
    if (currSize < mMinSizeClique)return false;
    return true;
}



template<class T>
void protspace::GraphMatch<T>::addClique(const std::vector<Vertex *>& newClique)
{
    if (newClique.size()< mMinSizeClique)return;
    mTmpresultlist.push_back(newClique);
    veList& list = mTmpresultlist.at(mTmpresultlist.size()-1);
    std::sort(list.begin(),list.end());

#ifdef GRAPH_MATCH_DEBUG
    std::cout<<"CLIQUE:";
    for(size_t ik=0;ik<newClique.size();++ik)
        std::cout << newClique.at(ik)->getMID()<< " ";std::cout<<std::endl;
    std::cout <<"TOTAL N CLIQUE:"<<mTmpresultlist.size()<<std::endl;
#endif
    if (mTmpresultlist.size()>= mMaxCliqueAllowed)
    {
        filterCliques();
        if (mTmpresultlist.size() <mMaxCliqueAllowed)return;
        std::cerr<<"WARNING - MAX NUMBER OF CLIQUE REACHED"<<std::endl;
        mMaxCliqueAllowedReached=true;
    }
}





template<class T> void protspace::GraphMatch<T>::calcCliques()
try{
    mMaxCliqueAllowedReached=false;
    cliquelist.clear();
    mOrder.clear();
    mTmpresultlist.clear();
    // mLevelCand.clear();

    if (mProdGraph.numVertex()==0)
        throw_line("110401",
                   "GraphMatch::calcCliques",
                   "No Pairs defined");
    if (mProdGraph.numEdges()==0)
        throw_line("110402",
                   "GraphMatch::calcCliques",
                   "No Edges defined");

    //////////////////////STEP1: Defining order
    getOrder();

    //////////////////////STEP2 : Searching


    mLevelCand.resize(mNPair,mNPair,false);

    size_t posNew;
    veList& newClique = mPoolVe.acquireObject(posNew);
    size_t nCand=0;
    for (size_t i=0;i<mNPair;++i)
    {

        Vertex& curr_look=mPairList.get(mOrder.at(i)).vertex;
        nCand=getCandidates(i,curr_look,0);
#ifdef GRAPH_MATCH_DEBUG
        std::cout << "LOOKING AT "<<curr_look.getMID()<<std::endl<<"  "<<nCand<<" Candidates :";
#endif

        if ( (!mIsVirtual && getUniqRefCand(0)+1< mMinSizeClique)|| nCand==1)
        {
#ifdef GRAPH_MATCH_DEBUG
            std::cout <<"NOT ENOUGH CANDIDATES"<<std::endl;
#endif
            continue;
        }
        newClique.clear();
        newClique.push_back(&curr_look);

        seekNextCliqueElement(newClique,1 );
    }
mPoolVe.releaseObject(posNew);
    //std::cout << "Number of maximal clique found : "<<mTmpresultlist.size()<<std::endl;
    convertToClique();

}catch(ProtExcept &e)
{
    ///200101 Bad allocation
    e.addHierarchy("GraphMatch::calcCliques");
    throw;
}


template<class T>
size_t protspace::GraphMatch<T>::fillCandidacy(
        const size_t& level,
        const size_t& curr_pos)
{
    size_t count=0;
    const size_t nPair = mOrder.size();
    const size_t colVal=level*mLevelCand.numColumns();
    const protspace::Vertex& vertex = mPairList.get(mOrder.at(curr_pos)).vertex;

    for (size_t pos2 = 0; pos2 < nPair;++pos2)
    {

        if (pos2<curr_pos+1|| !mLevelCand.getVal(level-1,pos2))
        {mLevelCand.val(colVal+pos2)=false;continue;}
        const Vertex& veTest = mPairList.get(mOrder.at(pos2)).vertex;
#ifdef GRAPH_MATCH_DEBUG
        std::cout << veTest.getMID()<<":(" <<mLevelCand.getVal(level-1,pos2)
             <<"-"<<vertex.hasEdgeWith(veTest)<<") " ;
#endif
        if (!vertex.hasEdgeWith(veTest))
        {
            mLevelCand.val(colVal+pos2)=false;
            continue;
        }
        mLevelCand.val(colVal+pos2)=true;
        //mLevelCand.setVal(level,pos2,true);
        count++;
#ifdef GRAPH_MATCH_DEBUG
        std::cout << veTest.getMID()<<" ";
#endif
    }
#ifdef GRAPH_MATCH_DEBUG
    std::cout<<std::endl;
#endif
    return count;
}

template<class T>
void protspace::GraphMatch<T>::seekNextCliqueElement(
       const std::vector<Vertex *>& clique , const size_t &level)
{
    const size_t nPair = mOrder.size();

    if (mMaxCliqueAllowedReached)return;
    std::vector<bool> status(nPair,false);
    #ifdef GRAPH_MATCH_DEBUG
    std::ostringstream oss;
    for (size_t i=0;i<=level;++i) oss << "#";
#endif
    size_t ncand=0;
    size_t posNewCli;
    veList& newclique=mPoolVe.acquireObject(posNewCli);
    const size_t newcliquesize=clique.size()+1;
    size_t nClique;

    for (size_t pos=0; pos < nPair;++pos)
    {

        if (!mLevelCand.getVal(level-1,pos)) continue;
        protspace::Vertex& vertex = mPairList.get(mOrder.at(pos)).vertex;
        if (!mFullScan)
        {
            for(size_t iLev=0;iLev <level-1;++iLev)
                mLevelCand.setVal(iLev,pos,false);
        }
#ifdef GRAPH_MATCH_DEBUG
        std::cout <<oss.str()<<"CANDIDATE "<< vertex.getMID()<<"\t";

        std::cout <<"\nVALID:";
        for(size_t i=0;i<nPair;++i)
        {
            if (mLevelCand.getVal(level-1,i))std::cout << mOrder.at(i)<<" ";
            status[i]=mLevelCand.getVal(level-1,i);
        }std::cout <<"\n";
#endif
        newclique=clique;newclique.push_back(&vertex);

        ncand=fillCandidacy(level,pos);
        //        cout <<oss.str()<<pos<<"\t"<<ncand<<"\t"<<getUniqRefCand(level) +newcliquesize<<"\t"<<mMinSizeClique<<endl;

        nClique=mTmpresultlist.size();

        if (ncand>0)
        {
            if (!mIsVirtual)
            {
                const size_t maxn=getUniqRefCand(level) +newcliquesize;

                /// former ncand replaced by getUniqRefCand(level)
                if (maxn==mMinSizeClique-1)continue;
                if (maxn< mMinSizeClique-1)return;
            }
            seekNextCliqueElement(newclique,level+1);

        }
        else
        {
            addClique(newclique);
            if (mMaxCliqueAllowedReached)return;
        }
        if (nClique == mTmpresultlist.size())continue;

    }
    mPoolVe.releaseObject(posNewCli);
}





template<class T>
const protspace::Edge&
protspace::GraphMatch<T>::getLink(const size_t& i)const throw(ProtExcept)
try{
    return mProdGraph.getEdge(i);}
catch(ProtExcept &e)
{
    e.addHierarchy("GraphMatch::getLink");throw;
}

template<class T>
protspace::Pair<T> &protspace::GraphMatch<T>::getPair(const size_t& nPair)
{

    if (nPair >= mPairList.size())
        throw_line("110201",
                   "GraphMatch::getPair",
                   "Given number is above the number of pairs");
    return mPairList.get(nPair);
}

template<class T>
const protspace::Pair<T> &protspace::GraphMatch<T>::getPair(const size_t& nPair)const{

    if (nPair >= mPairList.size())
        throw_line("110201",
                   "GraphMatch::getPair",
                   "Given number is above the number of pairs");
    return mPairList.get(nPair);
}


template<class T>
protspace::Clique<T> &protspace::GraphMatch<T>::getClique(const size_t& nClique)
{
    if (nClique >= cliquelist.size())
        throw_line("110301",
                   "GraphMatch::getClique",
                   "Given number is above the number of clique");
    return cliquelist.get(nClique);
}


template<class T>
const protspace::Clique<T> &
protspace::GraphMatch<T>::getClique(const size_t& nClique)const
{
    if (nClique >= cliquelist.size())
        throw_line("110501",
                   "GraphMatch::getClique",
                   "Given number is above the number of clique");
    return cliquelist.get(nClique);
}

template<class T>
void
protspace::GraphMatch<T>::getGammaCliques(const double& gamma)
{
    for(size_t iClique=0;iClique<cliquelist.size();++iClique)
    {
        Clique<T>& clique=cliquelist.get(iClique);
        std::vector<Pair<T>>& cliAllPair=clique.listpair;
        for(size_t iPair=0;iPair < mPairList.size();++iPair)
        {
            Pair<T>& paire=mPairList.get(iPair);
            bool found=false;
            for(size_t iCP=0;iCP< cliAllPair.size();++iCP)
            {
                if (&cliAllPair.at(iCP)!= &paire)continue;
                found=true;break;
            }
            if (found)continue;
            double sum=0;
            for(size_t iClPair=0;iClPair < cliAllPair.size();++iClPair)
            {
                Pair<T>& paireCl=cliAllPair.at(iClPair);
                if (paire.vertex.hasEdgeWith(paireCl.vertex))sum++;
            }
            sum/= (double)cliAllPair.size();
            std::cout <<sum<<std::endl;
            if (sum > gamma)cliAllPair.push_back(paire);
        }
    }
}
