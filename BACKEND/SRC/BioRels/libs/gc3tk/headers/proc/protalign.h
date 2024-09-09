#ifndef PROTALIGN_H
#define PROTALIGN_H

#include "headers/molecule/macromole.h"
#include "headers/math/rigidbody.h"
#include "headers/sequence/seqchain.h"
#include "headers/sequence/seqpair.h"
#include "headers/graph/graphmatch.h"
namespace protspace
{
class ProtAlign
{
public:
    struct ChainPair
    {
        SequenceChain mRefSeq;
        SequenceChain mCompSeq;
        SeqPairAlign mSeqAlign;
        /**
         * @brief ChainPair
         * @param pRef
         * @param pComp
         * @throw 520101    SequenceChain::update   Unrecognized AA
         */
        ChainPair(MMChain& pRef,MMChain& pComp);
        /**
         * @brief ChainPair
         * @param pRef
         * @param pComp
         * @throw 520101    SequenceChain::update   Unrecognized AA
         */
        ChainPair(MMChain& pRef,MMChain& pComp,SeqPairAlign& pAlign);
        ~ChainPair();
    };

protected:
    MacroMole& mReference;
    MacroMole& mComparison;
    RigidBody mAligner;
    GroupList<ChainPair> mSeqAlign;
    std::vector<MMResidue*> mResRlist,mResClist;
    std::vector<MMResidue*> mResUsedRlist,mResUsedClist;

    /**
     * @brief getResidueLists
     * @throw 520501    SequenceChain::getResidue    Position is above the number of entries
     * @throw 330601 MMChain::getResidue Given position is above the number of MMResidue
     */
    void getResidueLists();
    void applyRotation();
    void updateListCoords(Clique<MMResidue>& clique);



    /**
     * @brief genPairs
     * @param gmatch
     * @param distMatrix
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 200101 Bad allocation
     */
    void genPairs(GraphMatch<MMResidue>& gmatch,DMatrix& distMatrix);


    /**
     * @brief seekMinResThres
     * @param gmatch
     * @return
     * @throw 200101 Matrix - Bad allocation
     */
    size_t seekMinResThres(GraphMatch<MMResidue>& gmatch)const;

    /**
     * @brief genLinks
     * @param pThres
     * @param gmatch
     * @param distMatrix
     * @throw 030303 Bad allocation
     */
    void genLinks(const double& pThres,
                                           GraphMatch<MMResidue>& gmatch,
                                           DMatrix& distMatrix);
    void updateListCoords();

    /**
     * @brief scanThreshold
     * @param pThres
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 200101 Bad allocation
     * @throw 030303 Bad allocation
     * @throw 200101 Matrix - Bad allocation
     */
    void scanThreshold(double pThres);
    void createFromCA();
public:

    /**
     * @brief performSequenceAlignment
     * @throw 540101   SeqAlign::SeqAlign              Sequence cannot have gaps
     * @throw 540102   SeqAlign::SeqAlign              Bad allocation
     * @throw 540201   SeqAlign::loadMatrix            Unable to load blossum file
     * @throw 540103   SeqAlign::SeqAlign              Reference sequence is empty
     * @throw 540104   SeqAlign::SeqAlign              Compared sequence is empty
     */
    void performSequenceAlignment();
    MacroMole& getReference()const {return mReference;}
    MacroMole& getComparison() const {return mComparison;}
    ProtAlign(MacroMole& pRef,MacroMole& pComp);
    ~ProtAlign();
    const RigidBody& getAligner()const{return mAligner;}

    std::string printAlignment()const{

        Coords Center;
        for(size_t i=0;i<mComparison.numAtoms();++i)    Center+=mComparison.getAtom(i).pos();
        Center/=mComparison.numAtoms();

         const GroupList<double>& RotMat=mAligner.getParams().getMatrix();
        Coords Translate = Center-mAligner.getParams().getTransMob();
    //    std::cout << "TRANS INI : " << Translate<<std::endl;
        Translate.setxyz(Translate.x()*RotMat.get(0)+Translate.y()*RotMat.get(1)+Translate.z()*RotMat.get(2),
                            Translate.x()*RotMat.get(3)+Translate.y()*RotMat.get(4)+Translate.z()*RotMat.get(5),
                            Translate.x()*RotMat.get(6)+Translate.y()*RotMat.get(7)+Translate.z()*RotMat.get(8));
        Translate+=mAligner.getParams().getTransRigid();

        std::string s=mAligner.mat3Print();
        s+="["+std::to_string(Translate.getX())+" "+std::to_string(Translate.getY())+" "+std::to_string(Translate.getZ())+"]";
        return s;
    }
    /**
     * @brief addChainPair
     * @param pRefChainName
     * @param pCompChainName
     * @throw 640301   ProtAlign::addChainPair         Bad allocation
     * @throw 351101   MacroMole::getChain             Chain with name not found
     * @throw 520101    SequenceChain::update   Unrecognized AA
     */
    void addChainPair(const std::string& pRefChainName,
                      const std::string& pCompChainName);


    /**
     * @brief addChainPair
     * @param pRefChain
     * @param pCompChain
     * @throw 640101   ProtAlign::addChainPair         Given reference chain is not part of the reference molecule
     * @throw 640102   ProtAlign::addChainPair         Given comparison chain is not part of the comparison molecule
     * @throw 640103   ProtAlign::addChainPair         Bad allocation
     * @throw 520101    SequenceChain::update   Unrecognized AA
     */
    void addChainPair(MMChain& pRefChain,
                      MMChain& pCompChain);

    /**
     * @brief addChainPair
     * @param pRefChain
     * @param pCompChain
     * @param pAlign
     * @throw 640201   ProtAlign::addChainPair         Given reference chain is not part of the reference molecule
     * @throw 640202   ProtAlign::addChainPair         Given comparison chain is not part of the comparison molecule
     * @throw 640203   ProtAlign::addChainPair         Bad allocation
     * @throw 520101    SequenceChain::update   Unrecognized AA
     */
    void addChainPair(MMChain& pRefChain,
                      MMChain& pCompChain,
                      SeqPairAlign &pAlign);



    void setResidueList(const std::vector<MMResidue*>& rlist,
                        const std::vector<MMResidue*>& clist){
        mResRlist=rlist;mResClist=clist;

    }
    bool isResInRefList(const MMResidue& res)const;
    bool isResInCompList(const MMResidue& res)const;



    /**
     * @brief align
     * @param pThres
     * @param wSeqAlign
     * @return
     * @throw 110101 GraphMatch::addPair Bad allocation
     * @throw 030201 Graph::addVertex - Bad allocation
     * @throw 200101 Bad allocation
     * @throw 030303 Bad allocation
     * @throw 200101 Matrix - Bad allocation
     * @throw 540101   SeqAlign::SeqAlign              Sequence cannot have gaps
     * @throw 540102   SeqAlign::SeqAlign              Bad allocation
     * @throw 540201   SeqAlign::loadMatrix            Unable to load blossum file
     * @throw 540103   SeqAlign::SeqAlign              Reference sequence is empty
     * @throw 540104   SeqAlign::SeqAlign              Compared sequence is empty
     * @throw 520501    SequenceChain::getResidue    Position is above the number of entries
     * @throw 330601 MMChain::getResidue Given position is above the number of MMResidue
     * @throw 640401   ProtAlign::align                No chain assigned
     * @throw  640402   ProtAlign::align                List of reference residue not given
     * @throw  640403   ProtAlign::align                List of comparison residue not given
     * @throw  640404   ProtAlign::align                Different size list
     */
    double align(const double& pThres=0.5, const bool &wSeqAlign=true, const bool &fromCA=false);



    ChainPair& getChainPair(const size_t& pos)const {return mSeqAlign.get(pos);}
    size_t numChainPair()const {return mSeqAlign.size();}
    const   std::vector<MMResidue*> getUsedRList()const {return mResUsedRlist;}
    const   std::vector<MMResidue*> getUsedCList()const {return mResUsedClist;}
    void clear();
    void applyRotation(protspace::MacroMole &mole);
};
}
#endif // PROTALIGN_H
