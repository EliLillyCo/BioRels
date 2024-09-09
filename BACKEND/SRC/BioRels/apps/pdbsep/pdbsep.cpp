#include <sstream>
#include "pdbsep.h"
#include "headers/proc/chainperception.h"
#include "headers/statics/intertypes.h"
#include "headers/parser/writerMOL2.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/math/grid_utils.h"
#include "headers/inters/intercomplex.h"
PDBSep::PDBSep(protspace::MacroMole &pMole, const std::string &pHEAD):
    mMole(pMole),mHEAD(pHEAD),
    wpdb(new protspace::WritePDB),

    mGrid(2,4),

    mWConvert(false),
    mWSiteMap(false),
    mWVolSite(false),
    mWSingleChain(false),
    mWReceptor(true),
    mWTrimer(false),
    mWPPI(false),
    mWLigand(true),
    mNGroup(0),
    mOutFormat(".pdb")
{
    wpdb->onlySelected(true);
}

PDBSep::~PDBSep()
{
    if (wpdb!=nullptr)delete wpdb;
}

void PDBSep::setConvertPath(const std::string &pFile)
{
    if (pFile.empty())return;
    mConvert.open(pFile);
    mWConvert=mConvert.is_open();
}

void PDBSep::setSiteMapPath(const std::string &pFile)
{
    if (pFile.empty())return;
    mSiteMap.open(pFile);
    mWSiteMap=mSiteMap.is_open();
}

void PDBSep::setVolSitePath(const std::string &pFile)
{
    if (pFile.empty())return;
    mVolSite.open(pFile);
    mWVolSite=mVolSite.is_open();
}

void PDBSep::setOutFormat(const std::string &p){

    if (p=="mol2" || p=="MOL2")
    {
        if (wpdb !=nullptr)delete wpdb;
        wpdb=new protspace::WriteMOL2();
        wpdb->onlySelected(true);
        mOutFormat=".mol2";
    }
    else if (p=="pdb" || p=="PDB")
    {
        if (wpdb !=nullptr)delete wpdb;
        wpdb=new protspace::WritePDB();
        wpdb->onlySelected(true);
        mOutFormat=".pdb";
    }
    else throw_line("","PDBSep::setOutFormat","only pdb/PDB mol2/MOL2 values allowed");
}

void PDBSep::proceed()
{
    protspace::WriteMOL2 mw(mHEAD+".mol2");mw.save(mMole);
    std::cout <<"PERCEIVE"<<std::endl;
    perceiveChains();
    std::cout <<"_"<<mWSingleChain<<"_"<<mWReceptor<<"_"<<mWPPI<<"_"<<mWTrimer<<"_"<<mWLigand<<"\n";
    if (mWSingleChain)  genSingleChain();
    if (mWReceptor)     genReceptor();
    if (mWPPI)          genPPI();
    if (mWTrimer)       genTrimer();
    if (mWLigand)       genLigand();
}



void PDBSep::mol2xml(const std::string &pOutFile)
{
    pugi::xml_node Message= mDocument.append_child("Message");
    pugi::xml_node mChains=Message.append_child("Chains");
    bool bd_ck[mMole.numBonds()]={false};
    for (size_t i=0;i<mMole.numChains();++i)
    {
        protspace::MMChain& mCh=mMole.getChain(i);
        pugi::xml_node xCh=mChains.append_child("Chain");
        xCh.append_attribute("name").set_value(mCh.getName().c_str());
        xCh.append_attribute("type").set_value(CHAINTYPE::typeToName.at(mCh.getType()).c_str());
        xCh.append_attribute("nres").set_value(std::to_string(mCh.numResidue()).c_str());
        pugi::xml_node xResidues=xCh.append_child("Residues");
        pugi::xml_node xBonds=xCh.append_child("Bonds");
        for (size_t iR=0;iR<mCh.numResidue();++iR)
        {
            protspace::MMResidue& mR=mCh.getResidue(iR);
            pugi::xml_node xRe=xResidues.append_child("Residue");
            xRe.append_attribute("rname").set_value(mR.getName().c_str());
            xRe.append_attribute("identifier").set_value(mR.getIdentifier().c_str());
            xRe.append_attribute("rid").set_value(std::to_string(mR.getFID()).c_str());
            if (mR.getResType()==RESTYPE::STANDARD_AA|| mR.getResType()==RESTYPE::MODIFIED_AA)
            {
                size_t pos=0;
                if (mR.getAtom("CA",pos)) xRe.append_attribute("cacoo").set_value((std::to_string(mR.getAtom(pos).pos().x())+";"+std::to_string(mR.getAtom(pos).pos().y())+";"+std::to_string(mR.getAtom(pos).pos().z())).c_str());

            }
            pugi::xml_node xAtoms=xRe.append_child("Atoms");
            for (size_t iA=0;iA<mR.numAtoms();++iA)
            {
                protspace::MMAtom& mA=mR.getAtom(iA);
                pugi::xml_node xA=xAtoms.append_child("Atom");
                xA.append_attribute("aname").set_value(mA.getName().c_str());
                xA.append_attribute("id").set_value(std::to_string(mA.getFID()).c_str());
                xA.append_attribute("charge").set_value(std::to_string(mA.getFormalCharge()).c_str());
                xA.append_attribute("mol2type").set_value(mA.getMOL2().c_str());
                xA.append_attribute("BFACTOR").set_value(std::to_string(mA.getBFactor()).c_str());
                xA.append_attribute("x").set_value(std::to_string(mA.pos().x()).c_str());
                xA.append_attribute("y").set_value(std::to_string(mA.pos().y()).c_str());
                xA.append_attribute("z").set_value(std::to_string(mA.pos().z()).c_str());
                xA.append_attribute("identifier").set_value(mA.getIdentifier().c_str());
                for (size_t iB=0;iB< mA.numBonds();++iB)
                {
                    protspace::MMBond& bd(mA.getBond(iB));
                    if (mA.getMID()>bd.getOtherAtom(&mA).getMID())continue;
                    bd_ck[bd.getMID()]=true;
                    pugi::xml_node xB=xBonds.append_child("Bond");
                    xB.append_attribute("atom1").set_value(bd.getAtom1().getIdentifier().c_str());
                    xB.append_attribute("atom2").set_value(bd.getAtom2().getIdentifier().c_str());
                    xB.append_attribute("type").set_value(BOND::typeToName.at(bd.getType()).c_str());

                }
            }
        }
    }

    for (size_t i=0;i<mMole.numBonds();++i)
    {
        protspace::MMBond&  bd=mMole.getBond(i);
        if (bd_ck[bd.getMID()])continue;
        std::cerr<<bd.toString()<<"\tBETWEEN CHAINS\n";
    }
    protspace::InterComplex IC(mMole);
    protspace::InterData ID;
    IC.calcInteractions(ID);

    pugi::xml_node xLigs=Message.append_child("Ligands");
    for (auto it:mListLigs)
    {
        pugi::xml_node  lig=xLigs.append_child("Ligand");
                lig.append_attribute("type").set_value(it.type.c_str());
        for (auto itR:it.listRes) lig.append_child("Residue").append_attribute("Identifier").set_value(itR->getIdentifier().c_str());
    }

    pugi::xml_node xInters=Message.append_child("Inters");
    for (size_t i=0;i< ID.count();++i)
    {
        protspace::InterObj& ob=ID.getInter(i);
        size_t ring1=getRingPosFromCenter(ob.getAtom1());
        const bool isNotRing1(ring1==mMole.numRings());
        const protspace::MMResidue& res1=(isNotRing1)?
                    ob.getAtom1().getResidue():
                    mMole.getRing(ring1).getResidue();
        size_t ring2=getRingPosFromCenter(ob.getAtom2());
        const bool isNotRing2(ring2==mMole.numRings());
        const protspace::MMResidue& res2=(isNotRing2)?
                    ob.getAtom2().getResidue():
                    mMole.getRing(ring2).getResidue();


        pugi::xml_node xO=xInters.append_child("Inter");
        pugi::xml_node xO2=xInters.append_child("Inter");
        xO.append_attribute("inter_type").set_value(INTER::typeToName.at(ob.getType()).c_str());
        xO.append_attribute("dist").set_value(ob.getDistance());
        xO2.append_attribute("inter_type").set_value(INTER::typeToName.at(ob.getType()).c_str());
        xO2.append_attribute("dist").set_value(ob.getDistance());
        if (ob.getAngle()!=100000)
        {
            xO.append_attribute("angle").set_value(ob.getAngle());
            xO2.append_attribute("angle").set_value(ob.getAngle());
        }

        xO.append_attribute("res1").set_value(res1.getIdentifier().c_str());
        xO2.append_attribute("res2").set_value(res1.getIdentifier().c_str());
            if (isNotRing1)
            {
                xO.append_attribute("atom1").set_value(ob.getAtom1().getIdentifier().c_str());
                xO.append_attribute("atom1_list").set_value(ob.getAtom1().getName().c_str());
                xO2.append_attribute("atom2").set_value(ob.getAtom1().getIdentifier().c_str());
                xO2.append_attribute("atom2_list").set_value(ob.getAtom1().getName().c_str());
            }
            else
            {

                const protspace::MMRing& ringT=mMole.getRing(ring1);
                std::vector<std::string> l;
                std::string t("");
                for(size_t i=0;i<ringT.numAtoms();++i)
                    l.push_back(ringT.getAtom(i).getName());
                std::sort(l.begin(),l.end());
                for(const std::string& p:l)
                    t+=p+"/";
                t.pop_back();
                xO.append_attribute("atom1_list").set_value(t.c_str());
                xO2.append_attribute("atom2_list").set_value(t.c_str());
            }

            xO.append_attribute("res2").set_value(res2.getIdentifier().c_str());
            xO2.append_attribute("res1").set_value(res2.getIdentifier().c_str());
                if (isNotRing2)
                {
                    xO.append_attribute("atom2").set_value(ob.getAtom2().getIdentifier().c_str());
                    xO.append_attribute("atom2_list").set_value(ob.getAtom2().getName().c_str());
                    xO2.append_attribute("atom1").set_value(ob.getAtom2().getIdentifier().c_str());
                    xO2.append_attribute("atom1_list").set_value(ob.getAtom2().getName().c_str());
                }
                else
                {

                    const protspace::MMRing& ringT=mMole.getRing(ring2);
                    std::vector<std::string> l;
                    std::string t("");
                    for(size_t i=0;i<ringT.numAtoms();++i)
                        l.push_back(ringT.getAtom(i).getName());
                    std::sort(l.begin(),l.end());
                    for(const std::string& p:l)
                        t+=p+"/";
                    t.pop_back();
                    xO.append_attribute("atom2_list").set_value(t.c_str());
                    xO2.append_attribute("atom1_list").set_value(t.c_str());
                }

    }



        std::ofstream ofs(pOutFile);
        if (!ofs.is_open())
            throw_line("2310101",
                       "CpdToXML::exportFile",
                       "Unable to open file "+pOutFile);
        std::ostringstream content;
        mDocument.save(content);
        mDocument.print(ofs);
        ofs.close();


}

void PDBSep::perceiveChains()
{
    protspace::ChainPerception ch;
    ch.process(mMole);
    ch.reassignOther(mMole);
    ch.getSingleton(mMole,mListSingleton);
    for(size_t i=0;i<mListSingleton.size();++i)
    {
        if(mListSingleton[i]->getResType()!=RESTYPE::WATER)continue;

        mWater.push_back(mListSingleton[i]);
        mListSingleton.erase(mListSingleton.begin()+i);
        --i;
    }
    mGrid.considerMolecule(mMole);
    mGrid.createGrid();
    group();
}

void PDBSep::group()
{
    if (mListSingleton.empty())return;
    //    std::cout <<mListSingleton.size()<<"\n";
    short groups[mListSingleton.size()];

    for(size_t i=0;i<mListSingleton.size();++i)groups[i]=-1;
    for(size_t i=0;i<mListSingleton.size();++i)
    {
        const protspace::MMResidue& resI=*mListSingleton[i];
        for(size_t j=i+1;j<mListSingleton.size();++j)
        {
            const protspace::MMResidue& resJ=*mListSingleton[j];
            //            std::cout <<resI.getIdentifier()<<"\t"<<resJ.getIdentifier()<<"\t"<<protspace::areLinked(resI,resJ)<<std::endl;
            if (!protspace::areLinked(resI,resJ))continue;
            signed short& group1 = groups[i];
            signed short& group2 = groups[j];
            //std::cout <<group1<<" " <<group2<<"\n";
            if (group1 ==-1 && group2 == -1)
            {
                group1=mNGroup;
                group2=mNGroup;mNGroup++;
            }
            else if (group1==group2 && group1 != -1){//std::cout <<"\n";
                return;}
            else if (group1==-1 && group2 != -1)    group1=group2;
            else if (group1!=-1 && group2 == -1)    group2=group1;
            else if (group1!=-1 && group2 != -1)
            {
                const signed short minV(std::min(group1,group2));
                const signed short maxV(std::max(group1,group2));
                for(size_t k=0;k<mListSingleton.size();++k)
                {
                    if (groups[k]==maxV)groups[k]=minV;
                    else if (groups[k]>maxV)groups[k]--;
                }
                mNGroup--;
            }
        }
    }
    //    std::cout <<mNGroup<<"\n";
    std::vector<protspace::MMResidue*> l;
    for(short i=-1; i<=mNGroup;++i)
    {
        l.clear();
        for(size_t j=0;j<mListSingleton.size();++j)
        {
            if (groups[j]!=i)continue;
            //            std::cout << mListSingleton[j]->getIdentifier()<<"\t"<<i<<std::endl;
            if (i==-1)
            {
                l.clear();
                l.push_back(mListSingleton[j]);
                mListGroup.push_back(l);
            }
            else
            {
                l.push_back(mListSingleton[j]);
            }
        }
        if (i==-1)continue;
        mListGroup.push_back(l);
    }
}

void PDBSep::prepSingleReceptor(const std::string& pChainName,const std::string& pFName)
{
    mMole.select(false);
    mMole.getChainFromName(pChainName)->select(true);
    for(auto i:mListSingleton)i->select(false);
    for(auto i:mWater) i->select(false);
    wpdb->setPath(pFName+mOutFormat);
    wpdb->save(mMole);
}

void PDBSep::addVolSiteLine(const std::string& pFname)
{
    if (!mWVolSite)return;

    mVolSite<<"mkdir "<<pFname<<"\n";
    mVolSite<<"cd "<<pFname<<"\n";
    mVolSite<<"IChem -name "<<pFname<<" volsite  ../"<<pFname<<mOutFormat<<"\n";
    mVolSite<<"cd ../\n";

}

void PDBSep::addConvertLine(const std::string& pFname)
{
    if (!mWConvert)return;
    mConvert<<"$SCHRODINGER/utilities/pdbconvert -ipdb "<<pFname<<mOutFormat<<" -omae "<<pFname<<".mae\n";

}

void PDBSep::addSiteMapLine(const std::string& pFnameP, const std::string& pFnameL)
{
    if (mWSiteMap)
    {
        mSiteFile.open(pFnameL+".in");
        mSiteFile<<"PROTEIN ../"<<pFnameP<<".mae\n"
                <<"LIGMAE ../"<<pFnameL<<".mae\n"
               <<"SITEBOX 10\n"
              <<"KEEPLOGS yes\n";
        mSiteFile.close();
        mSiteMap<<"mkdir "<<pFnameL<<"_s\n";
        mSiteMap<<"cd "<<pFnameL<<"_s\n";
        mSiteMap<<"sitemap -compress no -j SIT_"<<pFnameL<<" -i ../"<<pFnameL<<".in -HOST cluster  -WAIT\n";
        mSiteMap<<"cd ..\n";
    }
}

void PDBSep::genSingleChain()
{
    std::cout <<"GEN SINGLE CHAIN"<<std::endl;
    for(size_t iChain=0;iChain< mMole.numChains();++iChain)
    {
        protspace::MMChain& ch = mMole.getChain(iChain);
        if (ch.getType()==CHAINTYPE::UNDEFINED){continue;}
        if (ch.numResidue()==0)continue;
        std::cout <<"FILE\tSINGLE_CHAIN\t"<<ch.getName()<<"\t";

        if (ch.getType()==CHAINTYPE::NUCLEIC){std::cout <<"/\t/NUCLEIC\n";continue;}

        const std::string fname(mHEAD+"_chain_"+ch.getName());
        std::cout <<fname<<"\t"<<CHAINTYPE::typeToName.at(ch.getType())<<std::endl;
        prepSingleReceptor(ch.getName(),fname);
        listEntries[ch.getName()]=fname;
//        addSiteMapLine(fname,fname);
//        addConvertLine(fname);
//        addVolSiteLine(fname);

    }
}




void PDBSep::genReceptor()
/// Save full receptor
{
    std::cout <<"GEN RECEPTOR"<<std::endl;
    mMole.select(true);
    size_t nCh=0;
    for(size_t iChain=0;iChain< mMole.numChains();++iChain)
    {
        protspace::MMChain& ch = mMole.getChain(iChain);
        std::cout <<ch.getName()<<"\t"<<CHAINTYPE::typeToName.at(ch.getType())<<"\n";
        if (ch.getType()!=CHAINTYPE::PROTEIN)ch.select(false);
        else ++nCh;
    }
    if (nCh==1 && mWSingleChain)return;
    for(auto p:mListSingleton)p->select(false);
    for(auto p:mWater) p->select(false);
    std::cout <<"FILE\tRECEPTOR\t/\t"<<mHEAD+"_receptor\n";


    const std::string fname(mHEAD+"_receptor");
    wpdb->setPath(fname+mOutFormat);
    wpdb->save(mMole);
//    addSiteMapLine(fname,fname);
//    addConvertLine(fname);
//    addVolSiteLine(fname);
}

void PDBSep::genLigand()
{
    std::cout <<"GEN LIGAND"<<std::endl;
    std::ostringstream oss,oss2;
    bool isCof=false,isPro=false;
    std::vector<protspace::MMResidue*> list;
    std::vector<std::string> chList;
    std::map<uint16_t, short> counts;
    std::cout <<"N GROUP:"<<mListGroup.size()<<"\n";
    for (auto gr:mListGroup)
    {
        std::cout <<"GROUP "<<gr.size()<<"\n";
        if (gr.empty())continue;
        mMole.select(false);
        oss.str("");oss2.str("");
        isCof=false;isPro=false;
        //std::cout <<gr.size()<<std::endl;
        //std::vector<std::vector<protspace::MMResidue*>>
        list.clear();
        counts.clear();
        LigEntry LE;
        for (auto res:gr)
        {
             if (counts.find(res->getResType())==counts.end())counts[res->getResType()]=1;
                else counts[res->getResType()]++;
        oss2<<"_"<<res->getName();
            oss<<"_"<<res->getChainName()<<"_"<<res->getName()<<res->getFID();
            res->select(true);
            LE.listRes.push_back(res);
            if (res->getResType()==RESTYPE::COFACTOR)isCof=true;
            if ( res->getResType()==RESTYPE::PROSTHETIC)isPro=true;
            protspace::getResidueClose(list,*res,6,mGrid,false,true,false);
        }
        std::cout << "TEST:"<<oss.str()<<std::endl;
        if (gr.size()==1)
        {
            if (counts.find(RESTYPE::ION)!=counts.end()
              ||counts.find(RESTYPE::METAL)!=counts.end()
              ||counts.find(RESTYPE::ORGANOMET)!=counts.end()
              ||counts.find(RESTYPE::UNWANTED)!=counts.end())
            {
                if (gr[0]->numAtoms()==1)continue;
            }

        }
        ///Creating ligand file
        const std::string fnamel(mHEAD+oss.str()+"_ligand");
        protspace::WriteMOL2 mw;
        mw.onlySelected(true);
        mw.setPath(fnamel+".mol2");
        mw.save(mMole);

        if (isCof)     { std::cout <<"FILE\tCOFACTOR\t"<<oss.str().substr(1)<<"\t"<<fnamel<<"\t"<<oss2.str().substr(1)<<"\n";LE.type="COFACTOR";}
        else if (isPro){ std::cout <<"FILE\tPROSTHETIC\t"<<oss.str().substr(1)<<"\t"<<fnamel<<"\t"<<oss2.str().substr(1)<<"\n";LE.type="PROSTHETIC";}
        else           { std::cout <<"FILE\tLIGAND\t"<<oss.str().substr(1)<<"\t"<<fnamel<<"\t"<<oss2.str().substr(1)<<"\n";LE.type="LIGAND";}
        mListLigs.push_back(LE);


        /// Getting the list of chains around the ligand
        chList.clear();
        for(auto res:list)
        {
            if (res->getChain().getType()==RESTYPE::UNDEFINED)continue;
            chList.push_back(res->getChainName());
        }
        std::sort(chList.begin(),chList.end());
        chList.erase(std::unique(chList.begin(),chList.end()),chList.end());

        /// Creating the list of chains
        std::string prot_name(""),prot_file("");
        for(auto s:chList)prot_name+=s+"_";
        prot_name=prot_name.substr(0,prot_name.length()-1);

        /// Checking if the list of chains exists
        const auto it=listEntries.find(prot_name);
        if (it!=listEntries.end())
        {
            prot_file=(*it).second;
            std::cout <<"COMB\t"<<oss.str().substr(1)<<"\t"<<prot_name<<"\n";
        }
        else if (chList.size()==1)
        {
            prot_file=mHEAD+"_chain_"+chList[0];
            std::cout <<"FILE\tSINGLE_CHAIN\t"<<chList[0]<<"\t"<<prot_file<<"\n";
            std::cout <<"COMB\t"<<oss.str().substr(1)<<"\t"<<chList[0]<<"\n";
            prepSingleReceptor(chList[0],prot_file);
        }
        else if (chList.size()==2)
        {
            prot_file=mHEAD+"_cplx_"+chList[0]+"_"+chList[1];
            std::cout <<"FILE\tPPI\t"<<chList[0]<<"_"<<chList[1]<<"\t"<<prot_file<<"\n";
            std::cout <<"COMB\t"<<oss.str().substr(1)<<"\t"<<chList[0]<<"_"<<chList[1]<<"\n";
            prepPPI(chList[0],chList[1],prot_file);
        }
        else if (chList.size()==3)
        {
            prot_file=mHEAD+"_cplx_"+chList[0]+"_"+chList[1]+"_"+chList[2];
            std::cout <<"FILE\tPPI\t"<<chList[0]<<"_"<<chList[1]<<"_"<<chList[2]<<"\t"<<prot_file<<"\n";
            std::cout <<"COMB\t"<<oss.str().substr(1)<<"\t"<<chList[0]<<"_"<<chList[1]<<"_"<<chList[2]<<"\n";
            prepTrimer(chList[0],chList[1],chList[2],prot_file);
        }
        addSiteMapLine(prot_file,fnamel);
        addConvertLine(fnamel);

        if (mWVolSite)
        {
            mVolSite<<"mkdir "<<fnamel<<"\n";
            mVolSite<<"cd "<<fnamel<<"\n";
            mVolSite<<"IChem -name "<<fnamel<<" volsite ../"<<prot_file<<"."<<mOutFormat<<" ../"<<fnamel<<mOutFormat<<"\n";
            mVolSite<<"cd ../\n";
        }
        if ((!isCof && !isPro)|| gr.size()>1)continue;
        if (chList.size()==1 && mMole.getChainFromName(chList[0])->getType()==CHAINTYPE::NUCLEIC)continue;


        mMole.select(false);
         for(auto s:chList)mMole.getChainFromName(s)->select(true);
        for(auto p:mListSingleton)p->select(false);
        for(auto p:mWater) p->select(false);
        gr[0]->select(true);
        std::string fname(mHEAD);
        if (chList.size()==1)fname+="_chain";
        else fname+="_cplx";
        for (auto s:chList)fname+="_"+s;
        fname+="_cof_"+gr[0]->getName()+"_"+std::to_string(gr[0]->getFID());
        if (isCof)std::cout <<"FILE\tCOF_PROT\t"<<oss.str().substr(1)<<"_P\t"<<fname<<"\n";
        else if (isPro)std::cout <<"FILE\tPRO_PROT\t"<<oss.str().substr(1)<<"_P\t"<<fname<<"\n";
std::cout <<"SIZE:"<<list.size()<<std::endl;
            for(size_t i=0;i<list.size();++i)
            {

                protspace::MMResidue& res=*list[i];
               // std::cout <<list[i]->getIdentifier()<<"\t"<<RESTYPE::typeToName.at(list[i]->getResType())<<std::endl;;
                if (!(res.getResType()==RESTYPE::LIGAND||
                      res.getResType()==RESTYPE::COFACTOR||
                      res.getResType()==RESTYPE::ORGANOMET||
                      res.getResType()==RESTYPE::SUGAR||
                      res.getResType()==RESTYPE::PROSTHETIC))continue;
                std::cout <<res.getIdentifier()<<"\n";
                bool found=false;
                for (auto r2:gr)
                {
                    std::cout <<"\t"<<r2->getIdentifier()<<"\n";
                    if (r2!=&res)continue;
                    std::cout <<"IN"<<std::endl;
                    found=true;
                    break;
                }
                std::cout <<"IN "<<found<<std::endl;
                if (found)continue;

                std::cout <<"COMB\t"<<res.getChainName()<<"_"<<res.getName()<<res.getFID()<<"\t"<<oss.str().substr(1)<<"_P"<<std::endl;
            }

        wpdb->setPath(fname+mOutFormat);
        wpdb->save(mMole);
        addSiteMapLine(fname,fname);
        addConvertLine(fname);
        addVolSiteLine(fname);

    }
}

void PDBSep::prepPPI(const std::string& pChain1, const std::string& pChain2,const std::string& pFName)
{
    mMole.select(false);
    mMole.getChainFromName(pChain1)->select(true);
    mMole.getChainFromName(pChain2)->select(true);
    for(auto p:mListSingleton)p->select(false);
    for(auto p:mWater) p->select(false);
    wpdb->setPath(pFName+mOutFormat);
    std::cout <<"SAVING "<<pFName+mOutFormat<<std::endl;
    wpdb->save(mMole);

}

void PDBSep::genPPI()
{
    std::cout <<"GEN PPI"<<std::endl;
    mMole.select(true);
    protspace::InterData datas;
    protspace::InterComplex icplx(mMole);
    icplx.calcInteractions(datas);

    for(size_t i=0;i<datas.count();++i)
    {
        protspace::InterObj& obj=datas.getInter(i);
        protspace::MMChain& c1=obj.getResidue1().getChain();
        protspace::MMChain& c2=obj.getResidue2().getChain();
        if (&c1==&c2)continue;
        const std::string name(std::min(c1.getName(),c2.getName())+"_"+std::max(c1.getName(),c2.getName()));
        if (listEntries.find(name)!=listEntries.end())continue;
        if (c1.getType()==CHAINTYPE::UNDEFINED||c2.getType()==CHAINTYPE::UNDEFINED){continue;}


        const std::string fname(mHEAD+"_CPLX_"+name);
        std::cout <<"FILE\tPPI\t"<<c1.getName()<<"_"<<c2.getName()<<"\t"<<fname<<"\n";
        listEntries[name]=fname;
        listEntries[std::max(c1.getName(),c2.getName())+"_"+std::min(c1.getName(),c2.getName())]=fname;
        prepPPI(c1.getName(),c2.getName(),fname);

        addSiteMapLine(fname,fname);
        addConvertLine(fname);
        addVolSiteLine(fname);

    }

}

void PDBSep::prepTrimer(const std::string& pChain1, const std::string& pChain2,const std::string& pChain3,const std::string& pFName)
{
    mMole.select(false);
    protspace::MMChain* ch1=mMole.getChainFromName(pChain1);
    assert(ch1!=nullptr);
    ch1->select(true);
    protspace::MMChain* ch2=mMole.getChainFromName(pChain2);
    assert(ch2!=nullptr);
    ch2->select(true);
    std::cout <<pChain3<<std::endl;
    protspace::MMChain* ch3=mMole.getChainFromName(pChain3);
    assert(ch3!=nullptr);
    ch3->select(true);
    if (ch1->getType()==CHAINTYPE::UNDEFINED||ch2->getType()==CHAINTYPE::UNDEFINED||ch3->getType()==CHAINTYPE::UNDEFINED)return;
    for(auto p:mListSingleton)p->select(false);
    for(auto p:mWater) p->select(false);
    wpdb->setPath(pFName+mOutFormat);
    std::cout << "SAVING TRIMER"<< pFName<<mOutFormat<<"\n";
    wpdb->save(mMole);

}
void PDBSep::genTrimer()
{
    std::cout <<"GEN TRIMER"<<std::endl;
    std::ostringstream oss;
    std::vector<std::string> tmp;
    const size_t nPPI(listEntries.size());
    for(size_t i=0;i<nPPI;++i)
    {
        auto it=listEntries.begin();
        std::advance(it,i);
        const std::string& r=(*it).first;
        const size_t pos=r.find("_");
        if (pos==std::string::npos)continue;
        if (r.find("_",pos+1)!=std::string::npos)continue;
        const std::string rr(r.substr(0,pos));
        const std::string rc(r.substr(pos+1));

        std::cout <<"TESTING "<<r<<std::endl;
        for(size_t j=0;j<nPPI;++j)
        {
            auto it2=listEntries.begin();
            std::advance(it2,j);
            const std::string& c=(*it2).first;
            const size_t pos2=c.find("_");
            if (pos2==std::string::npos)continue;
            const std::string cr(c.substr(0,pos2));
            const std::string cc(c.substr(pos2+1));
            if (c.find("_",pos2+1)!=std::string::npos)continue;

            std::cout <<"\tTESTING "<<c<<std::endl;
            if ((rr==cr && rc==cc)||(rr==cc && rc==cr))continue;
            tmp.clear();
            tmp.push_back(rr);
            tmp.push_back(rc);
            tmp.push_back(cr);
            tmp.push_back(cc);
            std::sort(tmp.begin(),tmp.end());
            tmp.erase(std::unique(tmp.begin(),tmp.end()),tmp.end());
            if (tmp.size()>3)continue;
            oss.str("");
            oss<<tmp[0]<<"_"<<tmp[1]<<"_"<<tmp[2];
            if (listEntries.find(oss.str())!=listEntries.end())continue;
            const std::string fname(mHEAD+"_CPLX_"+oss.str());
            std::cout <<"FILE\tPPI\t"<<tmp[0]<<"_"<<tmp[1]<<"_"<<tmp[2]<<"\t"<<fname<<"\n";

            listEntries[oss.str()]=fname;
            listEntries[tmp[0]+"_"+tmp[2]+"_"+tmp[1]]=fname;
            listEntries[tmp[1]+"_"+tmp[0]+"_"+tmp[2]]=fname;
            listEntries[tmp[1]+"_"+tmp[2]+"_"+tmp[0]]=fname;
            listEntries[tmp[2]+"_"+tmp[1]+"_"+tmp[0]]=fname;
            listEntries[tmp[2]+"_"+tmp[0]+"_"+tmp[1]]=fname;

            prepTrimer(tmp[0],tmp[1],tmp[2],fname);


            addSiteMapLine(fname,fname);
            addVolSiteLine(fname);
            addConvertLine(fname);
        }
    }
}
