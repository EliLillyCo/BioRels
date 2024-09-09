//
// Created by c188973 on 11/14/16.
//
#include <math.h>
#include <headers/parser/readers.h>
#include <headers/molecule/pdbentry_utils.h>
#include <headers/statics/intertypes.h>
#include <headers/statics/strutils.h>
#include <headers/math/grid.h>
#include <headers/math/grid_utils.h>
#include "headers/parser/ofstream_utils.h"
#include <headers/molecule/mmatom_utils.h>
#include "toolprotalign.h"
#include "headers/statics/logger.h"
#include "headers/proc/chainperception.h"
ToolProtAlign::ToolProtAlign():
    mEnforce(false),
    mwGMatch(false),
    mHasThreshold(false),
    mIsMultipleAnalysis(false),
    mCA(false),
    mWSwitch(true),
    mListChainPairs(),
    mOutFile(""),
    mAnalysisFile(""),
    mMinThreshold(0.5),
    mReference(""),
    mComparison(""),
    mAligner(mReference,mComparison),
    nAll(0),
    nSite(0),
    nAlign(0)
{
    for (size_t i=0;i<3;++i)for (size_t j=0;j<3;++j)
    {

        RMSDs[i][j]=0;
        RMSD_count[i][j]=0;
    }


}


void ToolProtAlign::load(const std::string& path, protspace::MacroMole& mole)
{
    try{
        protspace::readFile(mole,path);
        const std::string ext(protspace::getExtension(path));
        const bool isInt(protspace::isInternal(path));
        if (ext=="mol2"){protspace::prepareMolecule(mole,PREPRULE::ASSIGN_RESTYPE,isInt);return;}
        const size_t pos=path.find_last_of("/");
        if (pos !=std::string::npos)        mole.setName(path.substr(pos+1));
        else mole.setName(path);
        protspace::prepareMolecule(mole,PREPRULE::ALL,isInt);

    }catch(ProtExcept &e)
    {
        e.addHierarchy("ToolProtAlign::load");
        throw;
    }
}

void ToolProtAlign::setReference(const std::string& pFile,const std::string& pName)
try{
    mReference.clear();
    load(pFile,mReference);mReference.setName(pName);
}catch(ProtExcept &e)
{
    e.addHierarchy("ToolProtAlign::setReference");
    throw;
}


void ToolProtAlign::printAlignmentMatrix()const
{
    std::cout <<mAligner.printAlignment()<<std::endl;
}

void ToolProtAlign::setComparison(const std::string& pFile,const std::string& pName)
try{
    mComparison.clear();
    load(pFile,mComparison);
    mComparison.setName(pName);
}catch(ProtExcept &e)
{
    e.addHierarchy("ToolProtAlign::setComparison");
    throw;
}


void ToolProtAlign::prepChains() {
    try{
        if (mListChainPairs.empty()) {
            if (mReference.numChains()>1)
            {
                protspace::ChainPerception perc;
                perc.process(mReference);
                perc.reassignOther(mReference);
                if (mReference.numChains()>1)
                    throw_line("2710101",
                               "ToolProtAlign::prepChains",
                               "Too many chains in reference. Please specify chain pair through -p option ");
            }
            if (mComparison.numChains()>1)
            {
                protspace::ChainPerception perc;
                perc.process(mComparison);
                perc.reassignOther(mComparison);

                if (mComparison.numChains()>1)
                    throw_line("2710102",
                               "ToolProtAlign::prepChains",
                               "Too many chains in comparison. Please specify chain pair through -p option ");
            }

            mAligner.addChainPair(mReference.getChain(0),
                                  mComparison.getChain(0));

        }
        else {
            for(const auto it:mListChainPairs) mAligner.addChainPair(it.first, it.second);
        }
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="640101");/// Reference chain must be part of reference molecule
        assert(e.getId()!="640102");/// Comparison chain must be part of comparison molecule
        e.addHierarchy("ToolProtAlign::prepChains");
        throw;
    }
}


void ToolProtAlign::processPair()

try {

    mAligner.clear();
    prepChains();
    const bool hasResList(!mRawResidueList.empty());
    if (hasResList) perceiveResidueList();

    /// To align on the whole protein :
    ///    No residue list
    ///    Having a residue list but with the inverse rule
    if (mCA)
        mAligner.align(mMinThreshold,false,true);
    else if (mHasThreshold || mwGMatch) {
        if (!hasResList)mAligner.align(mMinThreshold);
        else if (mWhole)mAligner.align(mMinThreshold);
        else mAligner.align(mMinThreshold, false);
    }
    else if (!mHasThreshold && !mwGMatch && !hasResList)
        mAligner.align();
    else
        throw_line("2710301",
                   "ToolProtAlign::processPair",
                   "Wrong set of options");
    if (mWhole && hasResList) perceiveResidueList();

    if (!mOutFile.empty())
        protspace::saveFile(mComparison, mOutFile);
    if (!mAnalysisFile.empty())analyze();
}catch(ProtExcept &e)
{


    assert(e.getId()!="640401");////Chain must be assigned
    assert(e.getId()!="640402" && e.getId()!="640403");/// Residue list must be defined
    assert(e.getId()!="640404");/// Size must be the same
    e.addHierarchy("ToolProtAlign::processPairs");
    throw;
}


void ToolProtAlign::defineChainPairs(const std::string &pStrListChains) {
    try{
        size_t posUnder=0,formerPosUnder=0,posDot=0;
        std::string chainA,chainB;
        mListChainPairs.clear();std::vector<std::string> toks;
        protspace::tokenStr(pStrListChains,toks,"_");
        for(const auto& entry:toks)
        {
            posDot = entry.find(":");
            if (posDot==std::string::npos)
                throw_line("2710601",
                           "ToolProtAlign::defineChainPairs",
                           "Unrecognized chain  pair "+entry);
            chainA=entry.substr(0,posDot);
            chainB=entry.substr(posDot+1);
            mListChainPairs.insert(std::make_pair(chainA,chainB));
        }

    }catch(ProtExcept &e)
    {
        e.addHierarchy("ToolProtAlign::definechainPairs");
        throw;
    }
}




void ToolProtAlign::perceiveResidueList()
{
    try{

        std::vector<std::string> toks;
        std::vector<protspace::MMResidue*> listr,listc;
        bool isFirst=true;bool findMatch=false;
        double n=0,ntot=0;
        for(const std::string& line:mRawResidueList)
        {
            toks.clear();
            protspace::tokenStr(line,toks,"\t",false);

            if (toks.size()!=3 && toks.size()!= 6 && toks.size()!=1)continue;

            if (isFirst)
            {
                isFirst=false;

                if (toks.size()==6)findMatch=false;
                else if (toks.size()==3|| toks.size()==1)
                {
                    findMatch=true;
                    mAligner.performSequenceAlignment();
                }
                else
                    throw_line("2710201",
                               "ToolProtAlign::perceiveResidueList",
                               "Wrong number of columns in Residue List File for line :\n"+line);
            }

            ntot++;
            try{


                protspace::MMResidue& resR=
                        (toks.size()==1)?mAligner.getReference().getResidueByFID(atoi(toks.at(0).c_str())):
                                         mAligner.getReference()
                                         .getChain(toks.at(1))
                                         .getResidue(toks.at(0),atoi(toks.at(2).c_str()));

                if (!findMatch)
                {

                    const int pos=atoi(toks.at(5).c_str());
                    const std::string name = toks.at(3);

                    protspace::MMResidue& resC=mAligner.getComparison()
                            .getChain(toks.at(4))
                            .getResidue(name,pos);
                    listc.push_back(&resC);
                }
                else
                {

                    bool found=false;
                    for(size_t iCP=0;iCP<mAligner.numChainPair();++iCP)
                    {

                        protspace::ProtAlign::ChainPair& cpair=mAligner.getChainPair(iCP);
                        if (cpair.mRefSeq.getChain().getName()!=resR.getChainName())continue;
                        size_t pos =cpair.mRefSeq.getChain().getResiduePos(resR);
                        const protspace::SeqPairAlign& align= cpair.mSeqAlign;
                        const std::vector<int>& refpos=align.getRefPosVector();
                        const std::vector<int>& compos=align.getCompPosVector();
                        auto it=find(refpos.begin(),refpos.end(),pos);

                        if (it == refpos.end())break;

                        const size_t dist= (const size_t) std::distance(refpos.begin(), it);
                        if (compos.at(dist)==-1)continue;
                        found=true;

                        protspace::MMResidue& resC =
                                cpair.mCompSeq.getResidue(compos.at(dist));
                        //                    std::cout <<resC.getIdentifier()<<"\t"<<resR.getIdentifier()<<std::endl;
                        listc.push_back(&resC);
                        break;
                    }
                    if (!found)continue;
                }
                listr.push_back(&resR);
                n++;
            }catch(ProtExcept &e)
            {/// @throw 330901 No Residue found with the given parameters
                /// @throw 325001 ResidueUtils::residue1Lto3L   input should be 1 letter
                /// @throw 325002 ResidueUtils::residue1Lto3L   No name found
                /// @throw 330601 MMChain::getResidue Given position is above the number of MMResidue
                ///351101  MacroMole::getChain Chain with name not found
                ///351601   MacroMole::getResidueByFID  No residue found with this ID
                assert(e.getId()!="520501");/// Position in sequence should be wroking
                assert(e.getId()!="040101");///  Position in Chain pair should work
                e.addDescription("Line involved : "+line);
                e.addHierarchy("perceiveResidueList");
                if(mEnforce) throw;
                //                cerr <<e.toString();
            }
        }
        std::cout <<"% of residue considered : "<<n/ntot*100<<"%\n";
        mAligner.setResidueList(listr,listc);

    }catch(ProtExcept &e)
    {
        e.addHierarchy("ToolProtAlign::perceiveResidueList");
        throw;
    }
}

void ToolProtAlign::defineResidueList(const std::string &pFile) {
    try{
        std::ifstream ifs(pFile);
        if (!ifs.is_open())
            throw_line("2710501",
                       "ToolProtAlign::defineResidueList",
                       "Unable to open file "+pFile);
        std::string line;
        mRawResidueList.clear();
        while(!ifs.eof()) {
            safeGetline(ifs, line);
            if (line.empty())continue;
            mRawResidueList.push_back(line);
        }
        ifs.close();
    }catch(ProtExcept &e)
    {
        e.addHierarchy("ToolProtAlign::defineResidueList");
        throw;
    }
}

void ToolProtAlign::toggleAroundLigand(protspace::MacroMole& mole, protspace::Grid& grid)
{
    try{
        std::vector<protspace::MMResidue*> listR;
        mole.select(false);
        for(size_t iR=0;iR< mole.numResidue();++iR)
        {
            protspace::MMResidue& res=mole.getResidue(iR);
            if (res.getResType()!=RESTYPE::LIGAND)continue;
            protspace::getResidueClose(listR,res,6.5,grid);
            for(size_t i=0;i<listR.size();++i)
            {
                protspace::MMResidue& resn = *listR.at(i);
                if (&resn.getParent()!= &mole)continue;
                resn.select(true);
            }
        }
    }catch(ProtExcept &e)
    {
        e.addHierarchy("ToolProtAlign::toggleAroundLigand");
        throw;
    }
}


void ToolProtAlign::printHeader(std::ofstream& ofs)const
{
    ofs <<"refName"<<"\t"
       <<"compName"<<"\t"
      <<"r_RESNAME\t"
     <<"r_RESID\t"
    <<"r_CHAIN\t"
    <<"r_BSITE\t"
    <<"r_SEL\t"
    <<"c_RESNAME\t"
    <<"c_RESID\t"
    <<"c_CHAIN\t"
    <<"c_BSITE\t"
    <<"c_SEL\t"
    <<"RMSD_BACK\t"
    <<"RMSD_SIDE\t"
    <<"RMSD_RESIDUE\tALIGN_USED\n";
}

void ToolProtAlign::analyze()
{
    try{
        {
            protspace::Grid gridR(3, 3);
            gridR.considerMolecule(mAligner.getReference());
            gridR.considerMolecule(mAligner.getComparison());
            gridR.createGrid();

            toggleAroundLigand(mAligner.getReference(), gridR);
            toggleAroundLigand(mAligner.getComparison(), gridR);
        }
        const size_t fileSize((const size_t) protspace::filesize(mAnalysisFile));
        std::ofstream ofs(mAnalysisFile, mIsMultipleAnalysis?std::ios::app:std::ios::out);
        if (!ofs.is_open())
            throw_line("2710401",
                       "ToolProtAlign::analyze",
                       "Unable to open file "+mAnalysisFile);
        if (fileSize==0 || !mIsMultipleAnalysis) printHeader(ofs);



        const std::vector<protspace::MMResidue*> &cUsedList(mAligner.getUsedCList());
        const std::string &refName(mAligner.getReference().getName());
        const std::string &compName(mAligner.getComparison().getName());
        for (size_t i=0;i<3;++i)for (size_t j=0;j<3;++j)
        {

            RMSDs[i][j]=0;
            RMSD_count[i][j]=0;
        }
        nAlign=0; nSite=0;nAll=0;
        for(size_t iCP=0;iCP<mAligner.numChainPair();++iCP)
        {

            protspace::ProtAlign::ChainPair& cpair=mAligner.getChainPair(iCP);
            protspace::SeqPairAlign& align=cpair.mSeqAlign;

            const std::vector<int>& refpos=align.getRefPosVector();
            const std::vector<int>& compos =align.getCompPosVector();

            for(size_t i=0;i<refpos.size();++i)
            {

                const int& rpos = refpos.at(i);
                const int& cpos = compos.at(i);
                if (rpos == -1 || cpos==-1)continue;

                protspace::MMResidue& rres= cpair.mRefSeq.getResidue(rpos);
                protspace::MMResidue& cres= cpair.mCompSeq.getResidue(cpos);
                const bool isInList(mAligner.isResInRefList(rres));
                ofs <<refName<<"\t"
                   <<compName<<"\t"
                  <<rres.getName()<<"\t"
                 <<rres.getFID()<<"\t"
                <<rres.getChainName()<<"\t"
                <<((rres.isSelected())?"1":"0")<<"\t"
                <<isInList<<"\t"
                <<cres.getName()<<"\t"
                <<cres.getFID()<<"\t"
                <<cres.getChainName()<<"\t"
                <<((cres.isSelected())?"1":"0")<<"\t"
                <<mAligner.isResInCompList(cres)<<"\t"
                  ;
                if ((rres.getResType()!=RESTYPE::STANDARD_AA
                     &&rres.getResType()!=RESTYPE::MODIFIED_AA) ||
                        (cres.getResType()!=RESTYPE::STANDARD_AA
                         &&cres.getResType()!=RESTYPE::MODIFIED_AA)){
                    ofs<<"\t"<<std::endl;
                    continue;}

                updateRMSD(rres,cres, isInList,ofs);

            }

        }
        ofs.close();
        for(size_t i=0;i<3;++i)
            for(size_t j=0;j<3;++j)
            {
                if (RMSD_count[i][j]>0) RMSDs[i][j]/=RMSD_count[i][j];
                if (RMSDs[i][j] >0) RMSDs[i][j]=sqrt(RMSDs[i][j]);
            }

        /// 0 BSITE srmsdside srmsdback  srmsdall
        /// 1 ALL  grmsdside grmsdback grmsdall
        /// 2 ALIGNEMT alrmsdside alrmsdback alrmsdall
        /// 0 BSITE RMSD_count[0][0]  snback snall
        /// 1 ALL gnside gnback gnall
        /// 2 ALIGNMENT  alnside alnback alnall

        std::ostringstream oss;
        oss<<"TYPE\tSIDE\tBACKBONE\tALL\tCOUNTRES\tREFNAME\tCOMPNAME\n";
        LOG(oss.str());std::cout <<oss.str();oss.str("");
        oss<<"BSITE\t"<<RMSDs[0][0]<<" " <<RMSDs[0][1]<<" " <<RMSDs[0][2]<<"\t"<<nSite<<"\t"<<refName<<"\t"<<compName<<"\n";
        LOG(oss.str());std::cout <<oss.str();oss.str("");
        oss<<"ALL\t"<<RMSDs[1][0]<<" " <<RMSDs[1][1]<<" " <<RMSDs[1][2]<<"\t"<<nAll<<"\t"<<refName<<"\t"<<compName<<"\n";
        LOG(oss.str());std::cout <<oss.str();oss.str("");
        oss<<"ALIGNMENT\t"<<RMSDs[2][0]<<" " <<RMSDs[2][1]<<" " <<RMSDs[2][2]<<"\t"<<nAlign<<"\t"<<refName<<"\t"<<compName<<"\n";
        LOG(oss.str());std::cout <<oss.str();oss.str("");

    }catch(ProtExcept &e)
    {
        assert(e.getId()!="520501" && e.getId()!="330601");///Position in sequence should be ok
        assert(e.getId()!="220101");///Grid already created ? cannot happen
        e.addHierarchy("ToolProtAlign::analyze");
        throw;
    }
}

void ToolProtAlign::updateRMSD(const protspace::MMResidue& rres,
                               const protspace::MMResidue& cres,
                               const bool& isInList,
                               std::ofstream& ofs)
{
    const std::vector<protspace::MMResidue*> &rUsedList(mAligner.getUsedRList());
    double rmsdside=0, rmsdback=0,rmsdall=0;
    double nside=0,nback=0,nall=0;
    const bool sameres(rres.getName()==cres.getName());
    const bool isUsedForAl(std::find(rUsedList.begin(),rUsedList.end(),&rres)!=rUsedList.end());
    //std::cout<<"#################"<<rres.getIdentifier()<<"\t"<<cres.getIdentifier()<<std::endl;
    size_t posCRes=0;
    if (isUsedForAl)nAlign++;
    if (isInList) nSite++;
    nAll++;

    bool hasSwitch=false;

    if (mWSwitch) {
        if (rres.getName() == "LEU") hasSwitch = switchAtom(rres, cres, "CD1", "CD2");
        else if (rres.getName() == "VAL")hasSwitch = switchAtom(rres, cres, "CG1", "CG2");
        else if (rres.getName() == "PHE")hasSwitch = switchAtom(rres, cres, "CE1", "CE2");
        else if (rres.getName() == "TYR")hasSwitch = switchAtom(rres, cres, "CE1", "CE2");
        else if (rres.getName() == "ASP")hasSwitch = switchAtom(rres, cres, "OD1", "OD2");
        else if (rres.getName() == "GLU")hasSwitch = switchAtom(rres, cres, "OE1", "OE2");
        else if (rres.getName() == "ARG")hasSwitch = switchAtom(rres, cres, "NH1", "NH2");
    }
    std::string atomN="";
    for(size_t iA=0;iA< rres.numAtoms();++iA)
    {
        const protspace::MMAtom& atom=rres.getAtom(iA);
        atomN=atom.getName();
        if (atom.isHydrogen())continue;

        if (mWSwitch && hasSwitch)
        {
            //std::cout <<"SWITCH"<<std::endl;
            atomN=reassignName(atom.getName(),rres.getName());
            //if (atom.getName()!=atomN)            std::cout <<rres.getIdentifier()<<"\t"<<cres.getIdentifier()<<"\t"<<
            //            atom.getName()<<"\t"<<atomN<<std::endl;
        }
        try{

            static const std::string listN=" N CA C O ";

            const bool isBackBone(listN.find(" "+atom.getName()+" ")!=std::string::npos );
            if (!isBackBone && !sameres)continue;

            if (!cres.hasAtom(atomN,posCRes))continue;


            const protspace::MMAtom& atom2=cres.getAtom(posCRes);

            const double dist(atom.pos().distance_squared(atom2.pos()));
            //                     std::cout<<atom.getIdentifier()<<"\t"<<atom2.getIdentifier()<<"\t"<<dist<<std::endl;
            if (protspace::isBackbone(atom))
            {
                rmsdback+=dist;nback++;
                rmsdall+=dist;nall++;
                RMSDs[1][1]+=dist;RMSD_count[1][1]++;
                RMSDs[1][2]+=dist;RMSD_count[1][2]++;
                if (isInList)
                {
                    RMSDs[0][1]+=dist;RMSD_count[0][1]++;
                    RMSDs[0][2]+=dist;RMSD_count[0][2]++;
                }
                if (isUsedForAl)
                {
                    RMSDs[2][1]+=dist;RMSD_count[2][1]++;
                    RMSDs[2][2]+=dist;RMSD_count[2][2]++;
                }
            }
            else if (sameres)
            {
                rmsdside+=dist;nside++;
                rmsdall+=dist;nall++;
                RMSDs[1][0]+=dist;RMSD_count[1][0]++;
                RMSDs[1][2]+=dist;RMSD_count[1][2]++;
                if (isInList)
                {
                    RMSDs[0][0]+=dist;RMSD_count[0][0]++;
                    RMSDs[0][2]+=dist;RMSD_count[0][2]++;
                }
                if (isUsedForAl)
                {
                    RMSDs[2][0]+=dist;RMSD_count[2][0]++;
                    RMSDs[2][2]+=dist;RMSD_count[2][2]++;
                }
            }
        }catch(ProtExcept &e)
        {

        }
    }



    //             std::cout <<rmsdside<<" " <<rmsdback<<" " <<rmsdall<<std::endl;
    if (nside>0) rmsdside/=nside;
    if (nback >0)rmsdback/=nback;
    if (nall >0)rmsdall/=nall;
    //             std::cout <<rmsdside<<" " <<rmsdback<<" " <<rmsdall<<std::endl;
    if (rmsdback >0) rmsdback=sqrt(rmsdback);
    if (rmsdside>0)rmsdside=sqrt(rmsdside);
    if (rmsdall>0)rmsdall=sqrt(rmsdall);
    //             std::cout <<rmsdside<<" " <<rmsdback<<" " <<rmsdall<<std::endl;
    ofs <<rmsdback<<"\t"<<rmsdside<<"\t"<<rmsdall;
    if (isUsedForAl)
    {
        ofs<<"\t1\n";
    }else ofs<<"\t0\n";
}

bool ToolProtAlign::switchAtom(const protspace::MMResidue& rres,
                               const protspace::MMResidue& cres,
                               const std::string& pAtom1,
                               const std::string& pAtom2)const
{
    try
    {
        size_t posr1,posr2,posc1,posc2;
        if (!rres.hasAtom(pAtom1,posr1))return false;
        if (!rres.hasAtom(pAtom2,posr2))return false;
        if (!cres.hasAtom(pAtom1,posc1))return false;
        if (!cres.hasAtom(pAtom2,posc2))return false;
        protspace::MMAtom& rAt1=rres.getAtom(posr1);
        protspace::MMAtom& rAt2=rres.getAtom(posr2);
        protspace::MMAtom& cAt1=cres.getAtom(posc1);
        protspace::MMAtom& cAt2=cres.getAtom(posc2);
        const double d11=rAt1.dist(cAt1);
        const double d12=rAt1.dist(cAt2);
        const double d21=rAt2.dist(cAt1);
        const double d22=rAt2.dist(cAt2);
        //return (d21 < d11 && d12 < d22);
        const double same(d11+d22);
        const double switched(d12+d21);

        if (fabs(same-switched) < 0.5) {
            const double diffSame = fabs(same / 2 - d11) + fabs(same / 2 - d22);
            const double diffSwitch = fabs(switched / 2 - d12) + fabs(switched / 2 - d21);
            return diffSwitch< diffSame;
        }
        return same > switched;

    }catch(ProtExcept &e)
    {
        e.verboseLevel=2;
        LOG_ERR(e.getId()+"|ToolProtAlign::switchAtom|"+e.getDescription());
        return false;
    }
}

std::string ToolProtAlign::reassignName(const std::string& pName, const std::string& pResName)const {
    /// LEU CD1/CD2
    /// VAL CG1/CG2
    /// PHE CE1/CE2  CD1/CD2
    /// TYR CE1/CE2  CD1/CD2
    /// ASP OD1/OD2
    /// GLU OE1/OE2
    /// ARG NH1/NH2
    if (pResName=="LEU")
    {
        if (pName=="CD1")return "CD2"; else if (pName=="CD2")return "CD1";
    }
    else if (pResName=="VAL")
    {
        if (pName=="CG1")return "CG2";  else if (pName=="CG2")return "CG1";
    }
    else if (pResName=="PHE")
    {
        if (pName=="CE1")return "CE2"; else if (pName=="CE2")return "CE1";
        if (pName=="CD1")return "CD2"; else if (pName=="CD2")return "CD1";
    }
    else if (pResName=="TYR")
    {
        if (pName=="CE1")return "CE2"; else if (pName=="CE2")return "CE1";
        if (pName=="CD1")return "CD2"; else if (pName=="CD2")return "CD1";
    }
    else if (pResName=="ASP")
    {
        if (pName=="OD1")return "OD2"; else if (pName=="OD2")return "OD1";
    }
    else if (pResName=="GLU")
    {
        if (pName=="OE1")return "OE2"; else if (pName=="OE2")return "OE1";

    }
    else if (pResName=="ARG")
    {
        if (pName=="NH1")return "NH2"; else if (pName=="NH2")return "NH1";
    }
    return pName;
}

