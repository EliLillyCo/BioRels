#include "headers/statics/argcv.h"
#include "pdbsep.h"
#include "headers/statics/logger.h"
#include "headers/statics/protExcept.h"
#include "headers/molecule/macromole.h"
#include "headers/math/grid.h"
#include "headers/math/grid_utils.h"
#include "headers/parser/readers.h"
#include "headers/molecule/hetmanager.h"
#include "headers/statics/intertypes.h"
#include "headers/proc/bondperception.h"
#include "headers/molecule/mmresidue_utils.h"
#include "headers/proc/matchtemplate.h"
#include "headers/parser/writerPDB.h"
#include"headers/proc/chainperception.h"
#include "headers/inters/intercomplex.h"
#include "headers/iwcore/macrotoiw.h"
void help()

{

}


int main(const  int argc, const char* argv[])
try{
    protspace::Argcv args(" -i -c -l -m -w -s -v -sc -ppi -trim "," -oXML -f -iN ",argc,argv,help,false,2);
    std::string naming;
    protspace::MacroMole mole;
    protspace::readFile(mole,args.getArg(0));

    protspace::HETManager& manager=protspace::HETManager::Instance();

    manager.assignResidueType(mole,true,false);
    protspace::BondPerception perc;
    perc.preProcessMolecule(mole);
    protspace::MatchTemplate matcht;
    if (args.hasOption("-i"))matcht.setIsInternal(true);
    if (args.getOptArg("-iN",naming))matcht.addInternalName(naming);
    matcht.processMolecule(mole);
    perc.postProcessMolecule(mole);
    MacroToIW MIW(mole);
    MIW.generateRings();
    protspace::ChainPerception chainp;
    chainp.process(mole);
    if (args.hasOption("-i"))chainp.reassignOther(mole);
    double CA=0,CB=0; size_t pos;
    for(size_t i=0;i<mole.numResidue();++i)
    {
        const protspace::MMResidue& res=mole.getResidue(i);
        if (res.getResType()!=RESTYPE::STANDARD_AA)continue;
        if (res.hasAtom("CA",pos))CA++;
        if (res.hasAtom("CB",pos))CB++;
        else if (res.getName()=="GLY")CB++;
    }
    if (CB/CA < 0.3)
    {
        std::cout <<"NOT ENOUGH CBETA\n";
        return 0;
    }
std::string val("");
//std::cout <<args.hasOption("-sc")<<std::endl;
    PDBSep psep(mole, args.getArg(1));

    if (args.hasOption("-c"))psep.setConvertPath("convert.sh");
    if (args.hasOption("-s"))psep.setSiteMapPath("gen_sitemap.sh");
    if (args.hasOption("-v"))psep.setVolSitePath("gen_volsite.sh");
    if (args.hasOption("-sc"))psep.setWSingleChain(true);
    if (args.hasOption("-ppi"))psep.setWPPI(true);
    if (args.getOptArg("-f",val))psep.setOutFormat(val);
    if (args.hasOption("-trim"))psep.setWTrimer(true);
    psep.proceed();
    if (args.getOptArg("-oXML",val))psep.mol2xml(val);

    LOG_FLUSH
            ELOG_FLUSH
            return EXIT_SUCCESS;

}catch(ProtExcept &e)
{
    std::cerr<<e.toString();
    LOG_ERR(e.toString())
            LOG_FLUSH
            ELOG_FLUSH
            return EXIT_FAILURE;
}

