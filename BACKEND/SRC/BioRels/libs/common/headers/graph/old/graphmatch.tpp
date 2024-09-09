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
const size_t protspace::GraphMatch<T>::mMaxCliqueAllowed=100000;



template<class T> protspace::GraphMatch<T>::GraphMatch()
{
    mListAllClique=false;
    mMinSizeClique=3;

    mFullScan=true;
}






template<class T>
protspace::Pair<T>&
protspace::GraphMatch<T>::addPair(T& obj1, T& obj2)
throw(ProtExcept)
try
{
    Pair<T> *paire=new Pair<T>(obj1,obj2,mProdGraph.addVertex());
    mPairList.push_back(paire);
    return *mPairList.at(mPairList.size()-1);
}catch(ProtExcept &e)
{
    e.addHierarchy("GraphMatch::addPair");
    //e.addHierarchy("Object 1 : "+obj1.toString());
    //e.addHierarchy("Object 1 : "+obj2.toString());
    throw;
}





template<class T> void
protspace::GraphMatch<T>::addLink(PairT &pair, PairT &pair2)throw(ProtExcept)
try
{
    mProdGraph.addEdge(pair.vertex,pair2.vertex);
}catch(ProtExcept &e)
{
    e.addHierarchy("GraphMatch::addLink");
    //    e.addHierarchy("Object 1 : \n"
    //                   +pair.obj1.toString()+"\n"
    //                   +pair.obj2.toString());
    //    e.addHierarchy("Object 2 : "
    //                   +pair2.obj1.toString()+"\n"
    //                   +pair2.obj2.toString());
}





template<class T> protspace::Edge&
protspace::GraphMatch<T>::addLinkwEdge(Pair<T> &pair, Pair<T> &pair2) throw(ProtExcept)
try
{
    return mProdGraph.addEdge(pair.vertex,pair2.vertex);
}
catch (ProtExcept &e)
{
    e.addHierarchy("GraphMatch::addLinkwEdge");
    throw;
}






template<class T>
protspace::GraphMatch<T>::~GraphMatch()
{

    for(size_t i=0;i<mPairList.size();++i)delete mPairList[i];
    for(size_t i=0;i<cliquelist.size();++i)delete cliquelist[i];

}





template<class T> void protspace::GraphMatch<T>::clear()
{
    for(size_t i=0;i<mPairList.size();++i)delete mPairList[i];
    for(size_t i=0;i<cliquelist.size();++i)delete cliquelist[i];

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
{
    std::multimap<size_t,const Vertex*> vertex_degree;
    for (size_t i=0;i<mProdGraph.numVertex();++i)
    {
        const Vertex& vertex = mProdGraph.getVertex(i);
        vertex_degree.insert(std::pair<size_t,const Vertex*>(vertex.numDot(),&vertex));
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
        const Vertex& veJ=mPairList.at(mOrder.at(j))->vertex;
        if (j>curr_i && curr_look.hasEdgeWith(veJ))
        {
            mLevelCand.setVal(level,j,true);nCandidates++;
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
    std::cout <<endl;
#endif
    return nCandidates;
}


template<class T> size_t
protspace::GraphMatch<T>::getUniqRefCand(
        const size_t& level)const
{

    std::vector<T*> list;
    for (size_t j=0; j<mNPair;++j)
    {
        if (!mLevelCand.getVal(level,j))continue;
        list.push_back(&mPairList.at(mOrder.at(j))->obj1);
    }
    sort(list.begin(),list.end());
    return std::distance(list.begin(),
                         std::unique(list.begin(),list.end()));
}



template<class T>
bool protspace::GraphMatch<T>::checkOverlap(const std::vector<Vertex*>& vl,
                                            const Clique<T> cl)const
{
    bool found=false;
    for(size_t iVl=0;iVl< vl.size();++iVl)
    {
        const Vertex& ve=*vl.at(iVl);found=false;
        for(size_t iCl=0;iCl<cl.listpair.size();++iCl)
        {
            const Vertex& pair=cl.listpair.at(iCl)->vertex;

            if (ve.getMID()!=pair.getMID())continue;
            found=true;
            break;
        }

        if (!found)return false;
    }
    return true;
}






template<class T>
void protspace::GraphMatch<T>::convertToClique()
{
    if (mListAllClique)
    {
        std::vector<std::vector<Vertex*> > full_clique_list;
        for (size_t iCl=0;iCl < mTmpresultlist.size();++iCl)
        {
            std::vector<Vertex*>& vl=mTmpresultlist.at((iCl));
            std::vector<Vertex*> velist;
            getNext(vl,velist,full_clique_list,0);
        }

        std::sort(full_clique_list.begin(),full_clique_list.end());
        for (std::vector<std::vector<Vertex*> >::const_iterator it= full_clique_list.begin();it != full_clique_list.end();++it)
        {
            const std::vector<Vertex*>& velist = *it;
            if (velist.size() < mMinSizeClique) continue;
            Clique<T> *newClique= new Clique<T>(velist.size());
            for (std::vector<Vertex*> ::const_iterator it2=velist.begin();it2 != velist.end();++it2)
            {
                newClique->listpair.push_back(mPairList.at((*it2)->getMID()));
            }
            cliquelist.push_back(newClique);
        }

        //  std::cout << "Number of cliques found : "<<cliquelist.size()<<std::endl;
    }
    else
    {
        std::multimap<size_t,size_t> list;
        for (size_t iCl=0;iCl < mTmpresultlist.size();++iCl)
        {
            const std::vector<Vertex*>& vl=mTmpresultlist.at((iCl));
            list.insert(std::pair<size_t,size_t>(vl.size(),iCl));
        }
        for(auto it=list.rbegin();it != list.rend();++it)
        {
            std::vector<Vertex*>& vl=mTmpresultlist.at((*it).second);
//            cout <<"TESTING:";
//            for(size_t i=0;i<vl.size();++i)cout << vl.at(i)->getMID()<<" ";
//            cout<<endl;
            bool fail=false;
            for(size_t iCli=0;iCli < cliquelist.size();++iCli)
            {
                const Clique<T>& t=*cliquelist.at(iCli);
//                cout <<"AGAINST:";
//                for(size_t i=0;i<t.listpair.size();++i)cout << t.listpair.at(i)->vertex.getMID()<<" ";
//                cout<<endl;
                if (checkOverlap(vl,t)) {fail=true;break;}
            }
          //  cout <<"FAIL:"<<fail<<endl;
            if (fail)continue;
             Clique<T> *newClique= new Clique<T>(vl.size());
            for (std::vector<Vertex*> ::const_iterator it2=vl.begin();it2 != vl.end();++it2)
            {
                Vertex& ve=*(*it2);
                newClique->listpair.push_back(mPairList.at(ve.getMID()));
            }
            cliquelist.push_back(newClique);
        }
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
void protspace::GraphMatch<T>::addClique(std::vector<Vertex *>& newClique)
{
    if (newClique.size()< mMinSizeClique)return;
    sort(newClique.begin(),newClique.end());
    #ifdef GRAPH_MATCH_DEBUG
    cout<<"CLIQUE:";
    for(size_t ik=0;ik<newClique.size();++ik)
        cout << newClique.at(ik)->getMID()<< " ";cout<<endl;
#endif
    mTmpresultlist.push_back(newClique);
    if (mTmpresultlist.size()>= mMaxCliqueAllowed)mMaxCliqueAllowedReached=true;
}





template<class T> void protspace::GraphMatch<T>::calcCliques()
{
    mMaxCliqueAllowedReached=false;
    cliquelist.clear();
    mOrder.clear();
    mTmpresultlist.clear();
   // mLevelCand.clear();

    //////////////////////STEP1: Defining order
    getOrder();

    //pthread_t threads[mNPair];

    //////////////////////STEP2 : Searching


    mLevelCand.resize(mNPair,mNPair,false);

//    for(size_t i=0;i<nPair;++i)
//    {
//        std::vector<bool> newCandidates(nPair,false);
//        mLevelCand.push_back(newCandidates);
//    }

//    std::vector<bool>& candidates=mLevelCand.at(0);


    std::vector<Vertex*> newClique;
    size_t nCand=0;
    for (size_t i=0;i<mNPair;++i)
    {

        Vertex& curr_look=mPairList.at(mOrder.at(i))->vertex;
#ifdef GRAPH_MATCH_DEBUG
        cout << "LOOKING AT "<<curr_look.getMID()<<endl<<"  Candidates :";
#endif
        nCand=getCandidates(i,curr_look,0);

//        cout <<"#"<<i<<"\t"<<nCand<<endl;
        ///former nCand replaced by getUniqRefCand(0)+1
        if (getUniqRefCand(0)+1< mMinSizeClique|| nCand==1)
        {
#ifdef GRAPH_MATCH_DEBUG
            std::cout <<"NOT ENOUGH CANDIDATES"<<endl;
#endif
            continue;
        }
        newClique.clear();
        newClique.push_back(&curr_look);

        seekNextCliqueElement(newClique,1 );
    }

    //std::cout << "Number of maximal clique found : "<<mTmpresultlist.size()<<std::endl;
    convertToClique();

}


template<class T>
size_t protspace::GraphMatch<T>::fillCandidacy(
        const size_t& level,
        const size_t& curr_pos)
{
    size_t count=0;
    const size_t nPair = mOrder.size();
    const size_t colVal=level*mLevelCand.numColumns();
    const protspace::Vertex& vertex = mPairList.at(mOrder.at(curr_pos))->vertex;

    for (size_t pos2 = 0; pos2 < nPair;++pos2)
    {

        if (pos2<curr_pos+1|| !mLevelCand.getVal(level-1,pos2))
        {mLevelCand.val(colVal+pos2)=false;continue;}
        const Vertex& veTest = mPairList.at(mOrder.at(pos2))->vertex;
        #ifdef GRAPH_MATCH_DEBUG
        cout << veTest.getMID()<<":(" <<mLevelCand.getVal(level-1,pos2)
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
        cout << veTest.getMID()<<" ";
#endif
    }
    #ifdef GRAPH_MATCH_DEBUG
    cout<<endl;
#endif
    return count;
}

template<class T>
void protspace::GraphMatch<T>::seekNextCliqueElement(
 std::vector<Vertex *> clique , const size_t &level)
{
    const size_t nPair = mOrder.size();
    #ifdef GRAPH_MATCH_DEBUG
    ostringstream oss;
    for (size_t i=0;i<=level;++i) oss << "  ";
    #endif
    if (mMaxCliqueAllowedReached)return;
    std::ostringstream oss;
    for (size_t i=0;i<=level;++i) oss << "#";

    size_t ncand=0;
    std::vector<Vertex *> newclique;
    const size_t newcliquesize=clique.size()+1;
    size_t nClique;

    for (size_t pos=0; pos < nPair;++pos)
    {

        if (!mLevelCand.getVal(level-1,pos)) continue;
        protspace::Vertex& vertex = mPairList.at(mOrder.at(pos))->vertex;
        if (!mFullScan)
        {
            for(size_t iLev=0;iLev <level;++iLev)
                mLevelCand.setVal(iLev,pos,false);
        }
      #ifdef GRAPH_MATCH_DEBUG
        cout <<oss.str()<<"CANDIDATE "<< vertex.getMID()<<"\t";
#endif
        newclique=clique;newclique.push_back(&vertex);

        ncand=fillCandidacy(level,pos);
//        cout <<oss.str()<<pos<<"\t"<<ncand<<"\t"<<getUniqRefCand(level) +newcliquesize<<"\t"<<mMinSizeClique<<endl;

        nClique=mTmpresultlist.size();

        if (ncand>0)
        {
            const size_t maxn=getUniqRefCand(level) +newcliquesize;
            /// former ncand replaced by getUniqRefCand(level)
            if (maxn==mMinSizeClique-1)continue;
            if (maxn< mMinSizeClique-1)return;
            seekNextCliqueElement(newclique,level+1);
        }
        else
        {
            addClique(newclique);
            if (mMaxCliqueAllowedReached)return;
        }
        if (nClique == mTmpresultlist.size())continue;

    }
}





template<class T>
const protspace::Edge&  protspace::GraphMatch<T>::getLink(const size_t& i)const throw(ProtExcept)
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
        throw_line("140101",
                   "GraphMatch::getPair",
                   "Given number is above the number of pairs");
    return *mPairList.at(nPair);
}

template<class T>
protspace::Clique<T> &protspace::GraphMatch<T>::getClique(const size_t& nClique)
{
    if (nClique >= cliquelist.size())
        throw_line("140201",
                   "GraphMatch::getClique",
                   "Given number is above the number of clique");
    return *cliquelist.at(nClique);
}


template<class T>
const protspace::Clique<T> &protspace::GraphMatch<T>::getClique(const size_t& nClique)const
{
    if (nClique >= cliquelist.size())
        throw_line("140201",
                   "GraphMatch::getClique",
                   "Given number is above the number of clique");
    return *cliquelist.at(nClique);
}


template<class T>
void
protspace::GraphMatch<T>::getGammaCliques(const double& gamma)
{
    for(size_t iClique=0;iClique<cliquelist.size();++iClique)
    {
        Clique<T>& clique=cliquelist.at(iClique);
        std::vector<Pair<T>>& cliAllPair=clique.listpair;
        for(size_t iPair=0;iPair < mPairList.size();++iPair)
        {
            Pair<T>& paire=mPairList.at(iPair);
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
